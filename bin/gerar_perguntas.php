<?php

declare(strict_types=1);

/**
 * Gera 26 perguntas novas por eixo (208 no total) com a Groq, para complementar
 * as 32 já curadas e chegar ao pool de 240 (30 por eixo).
 *
 *   docker compose exec app php bin/gerar_perguntas.php
 *
 * O resultado vai para storage/cache/perguntas_geradas.json e passa por uma
 * validação de quantidade, tamanho, duplicidade e direção antes de ser usado.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Models\Eixo;
use Services\GroqService;

$pdo = Database::pdo();
$groq = new GroqService($config['groq']);

if (!$groq->disponivel()) {
    fwrite(STDERR, "GROQ_API_KEY ausente.\n");
    exit(1);
}

$eixos = Eixo::todos($pdo);
$existentes = [];

foreach ($pdo->query('SELECT eixo_id, texto FROM perguntas ORDER BY ordem, id')->fetchAll() as $linha) {
    $existentes[(int) $linha['eixo_id']][] = $linha['texto'];
}

$saida = [];

foreach ($eixos as $eixo) {
    $id = (int) $eixo['id'];
    $nome = eixo_nome($eixo);
    $esq = eixo_polo($eixo, 'esq');
    $dir = eixo_polo($eixo, 'dir');
    $descricao = eixo_descricao($eixo);
    $antigas = implode("\n", array_map(static fn (string $t): string => '- ' . $t, $existentes[$id] ?? []));
    $subtemas = [
        'estetica' => 'enquadramento, luz, cor, textura, decupagem, composição, plano, montagem visual',
        'tom' => 'emoção, humor, catarse, melancolia, prazer, desconforto, afeto',
        'universo' => 'verossimilhança, mundo inventado, documentário, fantasia, ficção científica, cotidiano',
        'ritmo' => 'duração, velocidade, planos longos, corte, espera, tensão',
        'moralidade' => 'ética, ambiguidade, culpa, justiça, finais, anti-herói',
        'estrutura' => 'causalidade, linearidade, fragmentação, ponto de vista, tempo narrativo',
        'atmosfera' => 'close, paisagem, multidão, intimidade, espetáculo, escala',
        'epoca' => 'memória, passado, futuro, nostalgia, tecnologia, época',
    ];
    $tema = $subtemas[$eixo['slug']] ?? 'cinema';

    $sistema = <<<TXT
Você é um crítico de cinema brasileiro com formação em teoria do cinema (Bordwell, Bazin, Deleuze, Sontag) e experiência em festivais.
Estamos calibrando o eixo "{$nome}" do PersonaCut, que vai de "{$esq}" (esquerda) a "{$dir}" (direita).
Definição do eixo: {$descricao}

Escreva afirmações de gosto cinematográfico em primeira pessoa, específicas e sensoriais, usando SOMENTE estes subtemas do eixo: {$tema}.
Não fuja para outros eixos (por exemplo, não fale de duração no eixo de estética, nem de fotografia no eixo de moral). Não escreva opiniões políticas, não cite nomes de filmes, diretores ou pessoas, e não use clichês como "filme bom é aquele que...".
Cada afirmação deve ter entre 8 e 24 palavras e poder ser respondida por alguém que viu poucos filmes.
TXT;

    $usuario = <<<TXT
Já existem estas 4 afirmações no eixo (não repita nem parafraseie):
{$antigas}

Gere EXATAMENTE 26 afirmações NOVAS: 13 que concordam com o polo esquerdo ("{$esq}", direcao -1) e 13 que concordam com o polo direito ("{$dir}", direcao +1).
Alterne as direções e não repita a mesma ideia. Use frases curtas e concretas.
Responda APENAS com um array JSON de 26 objetos, no formato:
[{"texto":"...","direcao":-1},{"texto":"...","direcao":1}]
Sem markdown, sem comentários e sem explicações.
TXT;

    $itens = null;

    for ($tentativa = 1; $tentativa <= 2 && $itens === null; $tentativa++) {
        $resposta = $groq->chat([
            ['role' => 'system', 'content' => $sistema],
            ['role' => 'user', 'content' => $usuario],
        ], 1800, 0.7);

        if ($resposta === null) {
            fwrite(STDERR, "  ! {$nome}: falha na chamada\n");
            continue;
        }

        $json = $resposta['texto'];
        $inicio = strpos($json, '[');
        $fim = strrpos($json, ']');

        if ($inicio === false || $fim === false) {
            fwrite(STDERR, "  ! {$nome}: resposta sem JSON\n");
            continue;
        }

        $decodificado = json_decode(substr($json, $inicio, $fim - $inicio + 1), true);

        if (!is_array($decodificado)) {
            continue;
        }

        $limpos = [];
        $vistos = [];

        foreach ($decodificado as $item) {
            if (!is_array($item)) {
                continue;
            }

            $texto = trim((string) ($item['texto'] ?? ''));
            $direcao = (int) ($item['direcao'] ?? 0);
            $chave = mb_strtolower(preg_replace('/\s+/', ' ', $texto) ?? $texto);

            if ($direcao !== -1 && $direcao !== 1) {
                continue;
            }

            if (mb_strlen($texto) < 40 || mb_strlen($texto) > 190) {
                continue;
            }

            if (isset($vistos[$chave])) {
                continue;
            }

            $vistos[$chave] = true;
            $limpos[] = ['texto' => $texto, 'direcao' => $direcao];
        }

        $esqCount = count(array_filter($limpos, static fn (array $i): bool => $i['direcao'] === -1));
        $dirCount = count($limpos) - $esqCount;

        if (count($limpos) >= 24 && $esqCount >= 11 && $dirCount >= 11) {
            $itens = $limpos;
        } else {
            fwrite(STDERR, "  ! {$nome}: {$esqCount}/{$dirCount} — tentando de novo\n");
        }
    }

    if ($itens === null) {
        fwrite(STDERR, "  x {$nome}: sem itens válidos\n");
        continue;
    }

    $saida[$eixo['slug']] = $itens;
    printf("  ✓ %-14s %d itens (%d esq / %d dir)\n", $nome, count($itens), count(array_filter($itens, static fn (array $i): bool => $i['direcao'] === -1)), count(array_filter($itens, static fn (array $i): bool => $i['direcao'] === 1)));
    sleep(65);
}

$arquivo = dirname(__DIR__) . '/storage/cache/perguntas_geradas.json';
file_put_contents($arquivo, json_encode($saida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

printf("\n  %d eixos gerados em %s\n", count($saida), $arquivo);
