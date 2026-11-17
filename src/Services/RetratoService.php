<?php

declare(strict_types=1);

namespace Services;

/**
 * Transforma o perfil calculado em um retrato redigido por IA.
 *
 * O texto é uma camada de redação: não altera vetor, arquétipo, afinidade ou
 * raridade. O prompt recebe apenas dados públicos do catálogo e o vetor.
 */
final class RetratoService
{
    public const PROMPT_VERSAO = 'retrato-v2';

    public function __construct(private readonly GroqService $groq)
    {
    }

    public function disponivel(): bool
    {
        return $this->groq->disponivel();
    }

    /**
     * @param array<string, mixed> $perfil view model do PerfilController
     * @return array{texto: string, modelo: string, tokens: int|null, prompt_versao: string}|null
     */
    public function gerar(array $perfil): ?array
    {
        if (!$this->groq->disponivel()) {
            return null;
        }

        $resposta = $this->groq->chat($this->mensagens($perfil), 800, 0.7);

        if ($resposta === null) {
            return null;
        }

        $texto = $this->limpar($resposta['texto']);

        if (!$this->valido($texto)) {
            return null;
        }

        return [
            'texto' => $texto,
            'modelo' => $resposta['modelo'],
            'tokens' => $resposta['tokens'],
            'prompt_versao' => self::PROMPT_VERSAO,
        ];
    }

    /**
     * Dados enviados ao modelo. Público para testar o prompt sem chamar a API.
     *
     * @param array<string, mixed> $perfil
     * @return array<string, mixed>
     */
    public function payload(array $perfil): array
    {
        $arquetipo = $perfil['arquetipo'] ?? null;

        return [
            'arquetipo' => [
                'titulo' => $arquetipo['titulo'] ?? 'O Espectador Neutro',
                'descricao' => $arquetipo['descricao'] ?? '',
                'ritual' => $arquetipo['ritual_cinema'] ?? '',
            ],
            'eixos' => array_map(
                static fn (array $linha): array => [
                    'nome' => \eixo_nome($linha['eixo']),
                    'polo' => $linha['polo'] === 'D' ? $linha['rotulo_dir'] : $linha['rotulo_esq'],
                    'pct' => $linha['polo'] === 'D' ? $linha['pct_dir'] : $linha['pct_esq'],
                    'intensidade' => $linha['intensidade'],
                ],
                $perfil['linhas'] ?? []
            ),
            'personagens' => array_map(
                static fn (array $p): array => [
                    'nome' => $p['nome'],
                    'obra' => $p['obra_origem'],
                    'ano' => $p['ano'],
                    'bio' => $p['bio'],
                    'afinidade' => $p['afinidade'],
                ],
                $perfil['personagens'] ?? []
            ),
            'diretor' => $perfil['diretor'] !== null ? [
                'nome' => $perfil['diretor']['nome'],
                'bio' => $perfil['diretor']['bio'],
                'obra' => $perfil['diretor']['obra_origem'],
                'afinidade' => $perfil['diretor']['afinidade'],
            ] : null,
            'estudio' => $perfil['estudio'] !== null ? [
                'nome' => $perfil['estudio']['nome'],
                'bio' => $perfil['estudio']['bio'],
                'obra' => $perfil['estudio']['obra_origem'],
                'afinidade' => $perfil['estudio']['afinidade'],
            ] : null,
            'estante' => array_map(
                static fn (array $o): array => ['titulo' => $o['titulo'], 'ano' => $o['ano']],
                array_slice($perfil['estante'] ?? [], 0, 8)
            ),
            'raridade' => [
                'um_em' => $perfil['raridade']['um_em'] ?? null,
                'percentual' => $perfil['raridade']['percentual'] ?? null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $perfil
     * @return list<array{role: string, content: string}>
     */
    public function mensagens(array $perfil): array
    {
        $sistema = <<<'TXT'
Você escreve, em português do Brasil, um retrato curto do gosto de cinema de uma pessoa.

Como escrever:
- Fale como alguém que entende de cinema conversando com um amigo: claro, direto, sem pose.
- Frases curtas. Evite jargão de crítica ("diegese", "catarse", "mise-en-scène", "imagem-tempo", "narrativa diegética") e adjetivos empilhados.
- Explique com exemplos concretos do que a pessoa gosta de ver na tela.
- Três parágrafos curtos, entre 120 e 170 palavras no total.
- Nada de listas, títulos, markdown, emojis ou negrito.

Regras invioláveis:
- Use APENAS os dados do JSON fornecido. Não invente filmes, pessoas, prêmios, cenas, datas ou fatos.
- Nunca cite um filme, diretor ou personagem que não esteja no JSON.
- Não fale sobre inteligência artificial, prompt, modelo ou algoritmo.
- Conecte os dois eixos dominantes aos personagens, ao diretor e ao estúdio fornecidos.
- Termine com um ritual de cinema curto e simples, coerente com o arquétipo.
TXT;

        return [
            ['role' => 'system', 'content' => $sistema],
            ['role' => 'user', 'content' => json_encode($this->payload($perfil), JSON_UNESCAPED_UNICODE)],
        ];
    }

    public function limpar(string $texto): string
    {
        $texto = strip_tags($texto);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return trim($texto);
    }

    public function valido(string $texto): bool
    {
        $tamanho = mb_strlen($texto);
        $minusculo = mb_strtolower($texto);

        if ($tamanho < 450 || $tamanho > 3000) {
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
