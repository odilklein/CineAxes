<?php

declare(strict_types=1);

namespace Services;

use Core\Vetor;

/**
 * Recomendação de obras a partir do vetor de 8 eixos.
 *
 * Não acessa banco nem rede: recebe arrays e devolve arrays, no mesmo padrão
 * de MatcherService e LikertCalculatorService (testável em tests/run.php).
 *
 * O vetor de uma obra é construído a partir de atributos objetivos (gênero,
 * país, idioma e década) e guardado em `catalogo_obras`. A `confianca` diz
 * quantos sinais alimentaram esse vetor: obras com um único gênero não devem
 * competir de igual para igual com obras ricas em metadados.
 */
final class RecomendacaoService
{
    /** @var array<string, mixed> */
    private readonly array $config;

    public function __construct(
        array $config,
        private readonly MatcherService $matcher
    ) {
        $this->config = $config;
    }

    /**
     * Vetor de gosto de uma obra.
     *
     * @param array<string, mixed> $atributos
     *        generos: list<int>, paises: list<string>, idioma: ?string,
     *        ano: ?int, tipo: 'filme'|'serie'
     * @return array{vetor: list<float>, confianca: int, sinais: int}
     */
    public function construir(array $atributos): array
    {
        $generos = $this->ids((array) ($atributos['generos'] ?? []));
        $paises = $this->codigos((array) ($atributos['paises'] ?? []));
        $idioma = mb_strtolower(trim((string) ($atributos['idioma'] ?? '')));
        $ano = isset($atributos['ano']) && is_numeric($atributos['ano']) ? (int) $atributos['ano'] : null;

        $pesos = (array) ($this->config['pesos_fonte'] ?? []);
        $mapaGeneros = (array) ($this->config['generos'] ?? []);
        $mapaPaises = (array) ($this->config['paises'] ?? []);
        $mapaIdiomas = (array) ($this->config['idiomas'] ?? []);

        $soma = array_fill(0, Vetor::EIXOS, 0.0);
        $sinais = 0;

        foreach ($generos as $id) {
            $definicao = $mapaGeneros[$id] ?? null;

            if (!is_array($definicao)) {
                continue;
            }

            $this->somar($soma, (array) ($definicao['eixos'] ?? []), (float) ($pesos['genero'] ?? 1.0));
            $sinais += $sinais < 5 ? 1 : 0;
        }

        foreach ($paises as $codigo) {
            $definicao = $mapaPaises[$codigo] ?? null;

            if (!is_array($definicao)) {
                continue;
            }

            $this->somar($soma, (array) ($definicao['eixos'] ?? []), (float) ($pesos['pais'] ?? 0.75));
            $sinais += $sinais < 6 ? 1 : 0;
            break;
        }

        if ($idioma !== '' && isset($mapaIdiomas[$idioma])) {
            $this->somar($soma, (array) $mapaIdiomas[$idioma], (float) ($pesos['idioma'] ?? 0.5));
            $sinais += $sinais < 6 ? 1 : 0;
        }

        if ($ano !== null && $ano >= 1900) {
            foreach ((array) ($this->config['decadas'] ?? []) as $faixa) {
                if ($ano > (int) ($faixa['ate'] ?? 0)) {
                    continue;
                }

                $this->somar($soma, (array) ($faixa['eixos'] ?? []), (float) ($pesos['decada'] ?? 0.35));
                $sinais += $sinais < 6 ? 1 : 0;
                break;
            }
        }

        $vetor = [];

        for ($i = 0; $i < Vetor::EIXOS; $i++) {
            $vetor[] = round(max(0.0, min(100.0, 50.0 + $soma[$i])), 2);
        }

        $base = max(1, (int) ($this->config['sinais_confianca'] ?? 6));
        $confianca = (int) min(100, round(100 * $sinais / $base));

        return [
            'vetor'     => $vetor,
            'confianca' => $confianca,
            'sinais'    => $sinais,
        ];
    }

    /**
     * Afinidade entre o perfil e uma obra, já corrigida pela confiança.
     *
     * A confiança não altera o vetor guardado (ele continua legível), mas
     * encolhe a nota em direção a 50 quando há pouco sinal. O piso de 60% evita
     * que uma obra sem metadados vire "50% compatível" e engane a ordenação.
     *
     * @param list<float> $vetorUsuario
     * @param array<string, mixed> $obra precisa de `vetor` e `confianca`
     */
    public function afinidade(array $vetorUsuario, array $obra): float
    {
        $vetor = $obra['vetor'] ?? [];

        if (!Vetor::valido($vetor)) {
            return 0.0;
        }

        $compatibilidade = $this->matcher->compatibilidade($vetorUsuario, $vetor);
        $confianca = max(0, min(100, (int) ($obra['confianca'] ?? 0)));
        $fator = 0.6 + 0.4 * $confianca / 100;

        return round(50.0 + ($compatibilidade - 50.0) * $fator, 1);
    }

    /**
     * Força relativa de uma obra para o perfil (0 a 1).
     *
     * Usada no ranking de plataformas: títulos neutros contribuem quase zero,
     * então volume bruto não decide sozinho.
     *
     * @param list<float> $vetorUsuario
     * @param array<string, mixed> $obra
     */
    public function forca(array $vetorUsuario, array $obra): float
    {
        if ((int) ($obra['confianca'] ?? 0) < $this->confiancaMinima()) {
            return 0.0;
        }

        return max(0.0, min(1.0, ($this->afinidade($vetorUsuario, $obra) - 50.0) / 50.0));
    }

    /**
     * Ordena obras por afinidade, anexando `afinidade` e `forca` em cada linha.
     *
     * @param list<float> $vetorUsuario
     * @param list<array<string, mixed>> $obras
     * @return list<array<string, mixed>>
     */
    public function rankear(array $vetorUsuario, array $obras): array
    {
        foreach ($obras as &$obra) {
            $obra['afinidade'] = $this->afinidade($vetorUsuario, $obra);
            $obra['forca'] = $this->forca($vetorUsuario, $obra);
            $obra['alinhamento'] = $this->alinhamento($vetorUsuario, (array) ($obra['vetor'] ?? []));
        }
        unset($obra);

        usort($obras, static function (array $a, array $b): int {
            $porAfinidade = ($b['afinidade'] ?? 0.0) <=> ($a['afinidade'] ?? 0.0);

            if ($porAfinidade !== 0) {
                return $porAfinidade;
            }

            return ((float) ($b['popularidade'] ?? 0.0)) <=> ((float) ($a['popularidade'] ?? 0.0));
        });

        return $obras;
    }

    /**
     * Alinhamento de direção entre o perfil e a obra (-1 a 1).
     *
     * A compatibilidade composta é generosa com obras perto do centro: quase
     * todo título "moderado" parece 75% compatível. O cosseno dos vetores
     * centrados captura o lado do mapa e é o que diferencia, por exemplo, um
     * perfil contemplativo de um catálogo frenético. Usado para ordenar as
     * trilhas (não substitui a afinidade exibida).
     *
     * @param list<float> $vetorUsuario
     * @param list<float> $vetorObra
     */
    public function alinhamento(array $vetorUsuario, array $vetorObra): float
    {
        if (!Vetor::valido($vetorObra)) {
            return 0.0;
        }

        $produto = 0.0;
        $normaUsuario = 0.0;
        $normaObra = 0.0;

        for ($i = 0; $i < Vetor::EIXOS; $i++) {
            $a = (float) ($vetorUsuario[$i] ?? 50) - 50.0;
            $b = (float) ($vetorObra[$i] ?? 50) - 50.0;

            $produto += $a * $b;
            $normaUsuario += $a ** 2;
            $normaObra += $b ** 2;
        }

        if ($normaUsuario <= 0.0 || $normaObra <= 0.0) {
            return 0.0;
        }

        return round($produto / sqrt($normaUsuario * $normaObra), 3);
    }

    /**
     * Dois eixos que mais explicam a obra em relação ao perfil.
     *
     * A diferença só entra se o perfil estiver do mesmo lado do desvio da obra;
     * assim a explicação nunca contradiz o vetor.
     *
     * @param list<float> $vetorUsuario
     * @param array<string, mixed> $obra
     * @return list<array{indice: int, delta: float}>
     */
    public function motivos(array $vetorUsuario, array $obra): array
    {
        $vetor = $obra['vetor'] ?? [];

        if (!Vetor::valido($vetor)) {
            return [];
        }

        $motivos = [];

        for ($i = 0; $i < Vetor::EIXOS; $i++) {
            $usuario = (float) ($vetorUsuario[$i] ?? 50);
            $valor = (float) ($vetor[$i] ?? 50);
            $desvioObra = $valor - 50.0;
            $desvioUsuario = $usuario - 50.0;

            if ($desvioObra === 0.0 || $desvioObra * $desvioUsuario <= 0.0) {
                continue;
            }

            $forca = min(abs($desvioObra), abs($desvioUsuario));

            if ($forca <= 0.0) {
                continue;
            }

            $motivos[] = [
                'indice' => $i + 1,
                'delta'  => round($forca, 1),
            ];
        }

        usort($motivos, static fn (array $a, array $b): int => $b['delta'] <=> $a['delta']);

        return array_slice($motivos, 0, 2);
    }

    /**
     * Primeira trilha compatível (a ordem do config define a prioridade).
     *
     * @param array<string, mixed> $obra
     */
    public function trilhaDe(array $obra): ?string
    {
        foreach (array_keys((array) ($this->config['trilhas'] ?? [])) as $slug) {
            if ($this->pertenceATrilha($obra, (string) $slug)) {
                return (string) $slug;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $obra */
    public function pertenceATrilha(array $obra, string $slug): bool
    {
        $trilha = (array) ($this->config['trilhas'][$slug] ?? []);

        if ($trilha === []) {
            return false;
        }

        $tipo = (string) ($trilha['tipo'] ?? 'ambos');
        $obraTipo = (string) ($obra['tipo'] ?? 'filme');

        if ($tipo !== 'ambos' && $tipo !== $obraTipo) {
            return false;
        }

        $paises = $this->codigos((array) ($trilha['paises'] ?? []));
        $paisesObra = $this->codigos((array) ($obra['paises'] ?? []));

        if ($paises !== [] && array_intersect($paises, $paisesObra) === []) {
            return false;
        }

        $generos = $this->ids((array) ($trilha['generos'] ?? []));
        $generosObra = $this->ids((array) ($obra['generos'] ?? []));

        if ($generos !== [] && array_intersect($generos, $generosObra) === []) {
            return false;
        }

        $semGeneros = $this->ids((array) ($trilha['sem_generos'] ?? []));

        if ($semGeneros !== [] && array_intersect($semGeneros, $generosObra) !== []) {
            return false;
        }

        return true;
    }

    public function confiancaMinima(): int
    {
        return max(0, (int) ($this->config['confianca_minima'] ?? 25));
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @param list<float> $soma
     * @param array<int|string, int|float> $deltas
     */
    private function somar(array &$soma, array $deltas, float $peso): void
    {
        foreach ($deltas as $eixo => $delta) {
            $indice = (int) $eixo - 1;

            if ($indice < 0 || $indice >= Vetor::EIXOS) {
                continue;
            }

            $soma[$indice] += (float) $delta * $peso;
        }
    }

    /** @return list<int> */
    private function ids(array $valores): array
    {
        $ids = [];

        foreach ($valores as $valor) {
            $id = is_array($valor) ? (int) ($valor['id'] ?? $valor['genero_id'] ?? 0) : (int) $valor;

            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return array_keys($ids);
    }

    /** @return list<string> */
    private function codigos(array $valores): array
    {
        $codigos = [];

        foreach ($valores as $valor) {
            $codigo = is_array($valor) ? (string) ($valor['codigo'] ?? $valor['code'] ?? '') : (string) $valor;
            $codigo = strtoupper(trim($codigo));

            if (preg_match('/^[A-Z]{2}$/', $codigo) === 1) {
                $codigos[$codigo] = true;
            }
        }

        return array_keys($codigos);
    }
}
