<?php

declare(strict_types=1);

namespace Services;

/**
 * Narrativa por perfil: um JSON único, gerado uma vez por código.
 *
 * A seleção de filmes, eixos e diretores é determinística. A IA só redige:
 * recebe o perfil já calculado e devolve frase, retrato, leituras, arquétipo,
 * diretor e "por que assistir" das descobertas. Nunca escolhe fatos.
 */
final class NarrativaService
{
    public const PROMPT_VERSAO = 'narrativa-v3';

    public function __construct(private readonly GroqService $groq)
    {
    }

    public function disponivel(): bool
    {
        return $this->groq->disponivel();
    }

    /** Detalhe da última falha da Groq (vazio quando deu certo). */
    public function ultimoErro(): string
    {
        return $this->groq->ultimoErro();
    }

    /**
     * @param array<string, mixed> $perfil view model do PerfilController
     * @return array{texto: string, frase: string|null, narrativa: array<string, mixed>, modelo: string, tokens: int|null, prompt_versao: string}|null
     */
    public function gerar(array $perfil): ?array
    {
        if (!$this->groq->disponivel()) {
            return null;
        }

        $resposta = null;
        $dados = null;
        $retrato = [];
        $frase = null;

        // Duas tentativas: o free tier da Groq às vezes devolve um JSON
        // incompleto (por exemplo, um único parágrafo). A segunda usa um teto
        // menor para caber no limite de tokens por minuto.
        foreach ([null, 700] as $limiteTokens) {
            $resposta = $this->groq->chat($this->mensagens($perfil), $limiteTokens, 0.7);

            if ($resposta === null) {
                continue;
            }

            $dados = $this->json($resposta['texto']);

            if ($dados === null) {
                $dados = null;

                continue;
            }

            $retrato = $this->paragrafos($dados['retrato'] ?? []);
            $frase = $this->frase($dados['frase'] ?? null);

            if ($retrato !== [] && $frase !== null) {
                break;
            }

            $dados = null;
        }

        if ($dados === null || $retrato === [] || $frase === null) {
            error_log('[PersonaCut][narrativa] resposta incompleta após duas tentativas');

            return null;
        }

        $texto = implode("\n\n", $retrato);

        if (!$this->valido($texto)) {
            return null;
        }

        $slugs = [];

        foreach ((array) ($perfil['linhas'] ?? []) as $linha) {
            $slugs[(string) ($linha['eixo']['slug'] ?? '')] = true;
        }

        $leituras = [];

        foreach (array_slice((array) ($dados['leituras'] ?? []), 0, 4) as $leitura) {
            if (!is_array($leitura)) {
                continue;
            }

            $slug = trim((string) ($leitura['slug'] ?? ''));
            $textoLeitura = $this->limpar((string) ($leitura['texto'] ?? ''));

            if ($slug === '' || !isset($slugs[$slug]) || mb_strlen($textoLeitura) < 120 || mb_strlen($textoLeitura) > 1600) {
                continue;
            }

            $leituras[] = ['slug' => $slug, 'texto' => $textoLeitura];
        }

        $permitidos = array_change_key_case($this->titulosPermitidos($perfil), CASE_LOWER);
        $descobertas = [];

        foreach (array_slice((array) ($dados['descobertas'] ?? []), 0, 4) as $achado) {
            if (!is_array($achado)) {
                continue;
            }

            $titulo = trim((string) ($achado['titulo'] ?? ''));
            $porque = $this->limpar((string) ($achado['porque'] ?? ''));

            if ($titulo === '' || $porque === '' || !isset($permitidos[mb_strtolower($titulo)])) {
                continue;
            }

            $descobertas[] = ['titulo' => $titulo, 'porque' => $porque];
        }

        $arquetipoTexto = $this->limpar((string) ($dados['arquetipo'] ?? ''));
        $tituloArquetipo = (string) ($perfil['arquetipo']['titulo'] ?? '');

        // A IA não pode renomear o arquétipo: se não citar o título fornecido,
        // o texto é descartado e a descrição do banco continua na tela.
        if ($tituloArquetipo !== '' && $arquetipoTexto !== ''
            && mb_stripos($arquetipoTexto, $tituloArquetipo) === false
        ) {
            $arquetipoTexto = '';
        }

        $diretorTexto = $this->limpar((string) ($dados['diretor'] ?? ''));
        $nomeDiretor = (string) ($perfil['diretor']['nome'] ?? '');

        if ($nomeDiretor !== '' && $diretorTexto !== ''
            && mb_stripos($diretorTexto, $nomeDiretor) === false
        ) {
            $diretorTexto = '';
        }

        $narrativa = [
            'retrato' => $retrato,
            'leituras' => $leituras,
            'arquetipo' => $arquetipoTexto,
            'diretor' => $diretorTexto,
            'descobertas' => $descobertas,
        ];

        return [
            'texto' => $texto,
            'frase' => $frase,
            'narrativa' => $narrativa,
            'modelo' => $resposta['modelo'],
            'tokens' => $resposta['tokens'],
            'prompt_versao' => self::PROMPT_VERSAO,
        ];
    }

    /**
     * @param array<string, mixed> $perfil
     * @return list<array{role: string, content: string}>
     */
    public function mensagens(array $perfil): array
    {
        $sistema = <<<'TXT'
Você escreve, em português do Brasil, um perfil de gosto cinematográfico.

Responda SOMENTE com JSON válido, sem markdown, neste formato:
{"frase":"até 90 caracteres","retrato":["parágrafo 1","parágrafo 2"],"leituras":[{"slug":"estetica","texto":"..."},{"slug":"tom","texto":"..."}],"arquetipo":"...","diretor":"...","descobertas":[{"titulo":"...","porque":"..."},{"titulo":"...","porque":"..."}]}

Como escrever:
- Fale do que a pessoa gosta de assistir, não de quem ela é.
- Use exemplos concretos apenas dos filmes, diretores e escolas fornecidos no JSON.
- Frases curtas, tom de conversa entre amigos que gostam de cinema.
- O retrato tem EXATAMENTE 2 parágrafos, cada um entre 100 e 320 caracteres.
- As leituras: EXATAMENTE 2, cada uma entre 120 e 400 caracteres, usando o slug do eixo correspondente.
- O campo "arquetipo" comenta o arquétipo em até 200 caracteres.
- O campo "diretor" comenta o diretor/escola em até 200 caracteres, citando as obras fornecidas.
- As "descobertas": EXATAMENTE 2, usando títulos já listados em descobertas; "porque" com até 140 caracteres.

Regras invioláveis:
- Use APENAS os dados do JSON. Não invente filmes, pessoas, prêmios, cenas, datas ou países.
- Nunca cite um título que não esteja no JSON.
- Não repita o mesmo filme em leituras diferentes; mencione cada título no máximo uma vez no retrato e nas leituras.
- Não fale sobre inteligência artificial, prompt, modelo, algoritmo, vetores ou percentuais.
- Não diga que a pessoa "é" um personagem, nem que alguém "filmaria a vida" dela.
- Não use a palavra "espelho" para comparar personalidades.
- Não use listas, emojis, negrito ou títulos.
TXT;

        return [
            ['role' => 'system', 'content' => $sistema],
            ['role' => 'user', 'content' => json_encode($this->payload($perfil), JSON_UNESCAPED_UNICODE)],
        ];
    }

    /**
     * @param array<string, mixed> $perfil
     * @return array<string, mixed>
     */
    public function payload(array $perfil): array
    {
        $arquetipo = $perfil['arquetipo'] ?? null;
        $filmes = [];

        foreach (array_slice((array) ($perfil['filmes_top'] ?? []), 0, 5) as $filme) {
            $filmes[] = [
                'titulo' => (string) ($filme['titulo'] ?? ''),
                'ano' => $filme['ano'] ?? null,
                'personagem' => $filme['personagem'] ?? null,
                'generos' => array_map(
                    static fn (array $genero): string => (string) ($genero['nome'] ?? ''),
                    (array) ($filme['generos'] ?? [])
                ),
                'paises' => array_map(
                    static fn (array $pais): string => (string) ($pais['nome'] ?? ''),
                    (array) ($filme['paises'] ?? [])
                ),
            ];
        }

        $descobertas = [];

        foreach ((array) ($perfil['descobertas'] ?? []) as $achado) {
            $descobertas[] = [
                'titulo' => (string) ($achado['titulo'] ?? ''),
                'ano' => $achado['ano'] ?? null,
                'porque' => (string) ($achado['porque'] ?? ''),
            ];
        }

        return [
            'arquetipo' => $arquetipo !== null ? [
                'titulo' => (string) ($arquetipo['titulo'] ?? ''),
                'descricao' => (string) ($arquetipo['descricao'] ?? ''),
                'ritual' => (string) ($arquetipo['ritual_cinema'] ?? ''),
            ] : null,
            'eixos' => array_map(
                static fn (array $linha): array => [
                    'slug' => (string) ($linha['eixo']['slug'] ?? ''),
                    'nome' => \eixo_nome($linha['eixo']),
                    'polo' => $linha['polo'] === 'D' ? $linha['rotulo_dir'] : $linha['rotulo_esq'],
                    'intensidade' => (string) ($linha['intensidade'] ?? 'tendencia'),
                ],
                (array) ($perfil['linhas'] ?? [])
            ),
            'confianca' => (string) ($perfil['confianca']['rotulo'] ?? 'nao_estimada'),
            'consistencia' => (string) ($perfil['consistencia']['rotulo'] ?? 'consistente'),
            'filmes' => $filmes,
            'diretor' => ($perfil['diretor'] ?? null) !== null ? [
                'nome' => (string) $perfil['diretor']['nome'],
                'obras' => array_map(
                    static fn (array $obra): string => (string) $obra['titulo'] . ' (' . (int) $obra['ano'] . ')',
                    array_slice((array) ($perfil['diretor']['obras'] ?? []), 0, 4)
                ),
            ] : null,
            'estudio' => ($perfil['estudio'] ?? null) !== null ? [
                'nome' => (string) $perfil['estudio']['nome'],
                'obras' => array_map(
                    static fn (array $obra): string => (string) $obra['titulo'] . ' (' . (int) $obra['ano'] . ')',
                    array_slice((array) ($perfil['estudio']['obras'] ?? []), 0, 4)
                ),
            ] : null,
            'descobertas' => $descobertas,
        ];
    }

    /** @return array<string, mixed>|null */
    private function json(string $texto): ?array
    {
        $texto = trim($texto);

        $inicio = strpos($texto, '{');
        $fim = strrpos($texto, '}');

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return null;
        }

        $dados = json_decode(substr($texto, $inicio, $fim - $inicio + 1), true);

        return is_array($dados) ? $dados : null;
    }

    /** @return list<string> */
    private function paragrafos(mixed $valor): array
    {
        if (is_string($valor)) {
            $valor = preg_split('/\n{2,}/', $valor) ?: [];
        }

        if (!is_array($valor)) {
            return [];
        }

        $saida = [];

        foreach ($valor as $paragrafo) {
            $limpo = $this->limpar((string) $paragrafo);

            if ($limpo !== '' && mb_strlen($limpo) >= 60) {
                $saida[] = $limpo;
            }
        }

        return array_slice($saida, 0, 4);
    }

    private function frase(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $frase = trim($valor, " \t\n\r\0\x0B\"“”");

        return mb_strlen($frase) >= 20 && mb_strlen($frase) <= 160 ? $frase : null;
    }

    /** @param array<string, mixed> $perfil @return array<string, bool> */
    private function titulosPermitidos(array $perfil): array
    {
        $titulos = [];

        foreach ((array) ($perfil['descobertas'] ?? []) as $achado) {
            $titulos[(string) ($achado['titulo'] ?? '')] = true;
        }

        foreach ((array) ($perfil['filmes_top'] ?? []) as $filme) {
            $titulos[(string) ($filme['titulo'] ?? '')] = true;
        }

        return $titulos;
    }

    private function limpar(string $texto): string
    {
        $texto = strip_tags($texto);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return trim($texto);
    }

    private function valido(string $texto): bool
    {
        $tamanho = mb_strlen($texto);
        $minusculo = mb_strtolower($texto);

        if ($tamanho < 240 || $tamanho > 2600) {
            return false;
        }

        foreach (['http://', 'https://', '**', 'como uma ia', 'como um modelo de linguagem', 'não posso'] as $proibido) {
            if (str_contains($minusculo, $proibido)) {
                return false;
            }
        }

        return substr_count($texto, '.') >= 3;
    }
}
