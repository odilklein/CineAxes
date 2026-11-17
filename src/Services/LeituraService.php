<?php

declare(strict_types=1);

namespace Services;

/**
 * Transforma os 8 eixos em leituras compreensíveis.
 *
 * A seleção é determinística: ordena os eixos pela distância do neutro, escolhe
 * os mais expressivos e usa a leitura curada do polo correspondente. Os eixos
 * continuam sendo o motor; aqui eles apenas viram consequência, exemplo e
 * reconhecimento.
 */
final class LeituraService
{
    /** Distância mínima do centro para um eixo virar leitura. */
    private const DISTANCIA_MINIMA = 4.0;

    /** Abaixo disso o perfil é de trânsito, não de escolha. */
    private const DISTANCIA_NEUTRA = 4.0;

    /** @param array<string, mixed> $config conteúdo de config/leituras.php */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @param list<array<string, mixed>> $linhas linhas prontas de linhasEixos()
     * @param list<array<string, mixed>> $personagens espelhos já ranqueados
     * @return list<array<string, mixed>>
     */
    public function selecionar(array $linhas, array $personagens = [], int $limite = 4): array
    {
        if ($linhas === []) {
            return [];
        }

        $ordenadas = [];

        foreach ($linhas as $indice => $linha) {
            $ordenadas[] = [
                'indice' => $indice,
                'linha'  => $linha,
                'distancia' => abs((float) ($linha['pct_dir'] ?? 50) - 50.0),
            ];
        }

        usort(
            $ordenadas,
            static fn (array $a, array $b): int => $b['distancia'] <=> $a['distancia']
        );

        if (($ordenadas[0]['distancia'] ?? 0.0) < self::DISTANCIA_NEUTRA) {
            return [$this->neutra()];
        }

        $leituras = [];

        foreach ($ordenadas as $ordenada) {
            if (count($leituras) >= $limite) {
                break;
            }

            if ($ordenada['distancia'] < self::DISTANCIA_MINIMA) {
                continue;
            }

            $leitura = $this->montar(
                $ordenada['linha'],
                $ordenada['indice'],
                $ordenada['distancia'],
                $personagens
            );

            if ($leitura !== null) {
                $leituras[] = $leitura;
            }
        }

        return $leituras !== [] ? $leituras : [$this->neutra()];
    }

    /**
     * @param array<string, mixed> $linha
     * @param list<array<string, mixed>> $personagens
     * @return array<string, mixed>|null
     */
    private function montar(array $linha, int $indice, float $distancia, array $personagens): ?array
    {
        $slug = (string) ($linha['eixo']['slug'] ?? '');
        $conteudo = $this->config['eixos'][$slug] ?? null;

        if ($slug === '' || !is_array($conteudo)) {
            return null;
        }

        $ehDireita = ($linha['polo'] ?? 'E') === 'D';
        $leitura = $conteudo[$ehDireita ? 'd' : 'e'] ?? null;

        if (!is_array($leitura)) {
            return null;
        }

        $pct = (float) ($ehDireita ? ($linha['pct_dir'] ?? 50) : ($linha['pct_esq'] ?? 50));
        $nome = trim((string) ($linha['eixo']['nome'] ?? ''));

        return [
            'slug'        => $slug,
            'eixo'        => $nome !== '' ? $nome : $slug,
            'polo'        => (string) ($ehDireita ? ($linha['rotulo_dir'] ?? '') : ($linha['rotulo_esq'] ?? '')),
            'pct'         => $pct,
            'intensidade' => (string) ($linha['intensidade'] ?? 'tendencia'),
            'forca'       => $this->rotuloForca($distancia),
            'cor'         => (string) ($linha['cor'] ?? ''),
            'manchete'    => (string) ($leitura['manchete'] ?? ''),
            'corpo'       => (string) ($leitura['corpo'] ?? ''),
            'combina'     => array_slice((array) ($leitura['combina'] ?? []), 0, 3),
            'cansa'       => (string) ($leitura['cansa'] ?? ''),
            'curto'       => (string) ($leitura['curto'] ?? ''),
            'espelho'     => $this->melhorEspelho($personagens, $indice, $ehDireita),
        ];
    }

    /** @return array<string, mixed> */
    private function neutra(): array
    {
        $neutra = $this->config['neutro'] ?? [];

        return [
            'slug'        => 'neutro',
            'eixo'        => 'Seu perfil',
            'polo'        => 'Entre os lados',
            'pct'         => null,
            'intensidade' => 'equilibrado',
            'forca'       => 'perfil de trânsito',
            'cor'         => '#8A6A45',
            'manchete'    => (string) ($neutra['manchete'] ?? ''),
            'corpo'       => (string) ($neutra['corpo'] ?? ''),
            'combina'     => array_slice((array) ($neutra['combina'] ?? []), 0, 3),
            'cansa'       => (string) ($neutra['cansa'] ?? ''),
            'curto'       => (string) ($neutra['curto'] ?? 'Entre os lados'),
            'espelho'     => null,
        ];
    }

    private function rotuloForca(float $distancia): string
    {
        foreach ((array) ($this->config['forcas'] ?? []) as $faixa) {
            if ($distancia >= (float) ($faixa['minimo'] ?? 0)) {
                return (string) ($faixa['rotulo'] ?? 'tendência');
            }
        }

        return 'tendência';
    }

    /**
     * Personagem espelho que mais puxa para o mesmo lado do eixo.
     *
     * @param list<array<string, mixed>> $personagens
     * @return array{nome: string, obra: string, ano: int|null}|null
     */
    private function melhorEspelho(array $personagens, int $indice, bool $ehDireita): ?array
    {
        $melhor = null;
        $maior = 8.0;

        foreach ($personagens as $personagem) {
            $valor = (float) (($personagem['vetor'] ?? [])[$indice] ?? 50.0);
            $mesmoLado = $ehDireita ? $valor > 50.0 : $valor < 50.0;

            if (!$mesmoLado) {
                continue;
            }

            $distancia = abs($valor - 50.0);

            if ($distancia <= $maior) {
                continue;
            }

            $maior = $distancia;
            $melhor = [
                'nome' => (string) ($personagem['nome'] ?? ''),
                'obra' => (string) ($personagem['obra_origem'] ?? ''),
                'ano'  => isset($personagem['ano']) ? (int) $personagem['ano'] : null,
            ];
        }

        return $melhor;
    }
}
