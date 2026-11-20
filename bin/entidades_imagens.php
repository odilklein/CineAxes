<?php

declare(strict_types=1);

/**
 * Preenche a imagem de diretores/escolas que o TMDB não trouxe, usando a
 * API pública da Wikipédia (pageimages) e, como último recurso, o pôster de
 * uma obra de referência da entidade.
 *
 *   docker compose exec app php bin/entidades_imagens.php
 *   docker compose exec app php bin/entidades_imagens.php --dry-run
 *
 * A imagem é gravada em `entidades_match.imagem_url`. Se não houver página
 * com imagem, a entidade continua com o monograma/selo do front.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$opcoes = getopt('', ['dry-run', 'ajuda']);

if (isset($opcoes['ajuda'])) {
    echo "Uso: php bin/entidades_imagens.php [--dry-run]\n";
    exit(0);
}

$dryRun = isset($opcoes['dry-run']);
$pdo = Database::pdo();

$entidades = $pdo->query(
    "SELECT id, tipo, nome
       FROM entidades_match
      WHERE tipo IN ('diretor', 'estudio')
        AND (imagem_url IS NULL OR imagem_url = '')
      ORDER BY tipo, nome"
)->fetchAll();

if ($entidades === []) {
    echo "Todas as entidades já têm imagem.\n";
    exit(0);
}

/**
 * Busca a miniatura da página na Wikipédia, tentando português e inglês.
 * `$exato` procura o título exato; sem ele, usa a busca textual.
 */
function buscarImagemWikipedia(string $termo, bool $exato = false): ?string
{
    foreach (['pt', 'en'] as $idioma) {
        $parametros = $exato
            ? 'action=query&titles=' . rawurlencode($termo)
            : 'action=query&generator=search&gsrsearch=' . rawurlencode($termo)
                . '&gsrlimit=1&gsrnamespace=0';

        $url = sprintf(
            'https://%s.wikipedia.org/w/api.php?%s&prop=pageimages&piprop=thumbnail&pithumbsize=500&format=json&formatversion=2',
            $idioma,
            $parametros
        );

        for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
            $curl = curl_init($url);

            if ($curl === false) {
                break;
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'PersonaCut/2.0 (projeto academico; contato local)',
            ]);

            $corpo = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);

            if ($status === 429) {
                sleep(2);
                continue;
            }

            if ($corpo === false || $status !== 200) {
                break;
            }

            $dados = json_decode((string) $corpo, true);
            $pagina = $dados['query']['pages'][0] ?? null;
            $fonte = is_array($pagina) ? ($pagina['thumbnail']['source'] ?? null) : null;

            if (is_string($fonte) && $fonte !== '') {
                return strtok($fonte, '?') ?: $fonte;
            }

            break;
        }

        usleep(350000);
    }

    return null;
}

/** Pôster de uma obra da entidade, quando a Wikipédia não tem imagem. */
$posterFallback = static function (PDO $pdo, int $entidadeId): ?string {
    $stmt = $pdo->prepare(
        'SELECT o.poster_path
           FROM entidades_obras eo
           JOIN catalogo_obras o ON o.tmdb_id = eo.tmdb_id
          WHERE eo.entidade_id = :id
            AND o.poster_path IS NOT NULL
          ORDER BY eo.ordem
          LIMIT 1'
    );
    $stmt->execute(['id' => $entidadeId]);
    $path = $stmt->fetchColumn();

    if (!is_string($path) || $path === '') {
        return null;
    }

    return 'https://image.tmdb.org/t/p/w500' . $path;
};

$atualizar = $pdo->prepare(
    'UPDATE entidades_match
        SET imagem_url = :imagem, enriquecido_em = NOW()
      WHERE id = :id'
);

$encontradas = 0;

foreach ($entidades as $entidade) {
    $nome = (string) $entidade['nome'];
    $termo = (string) $entidade['tipo'] === 'estudio' ? $nome . ' cinema' : $nome;

    $termo = match ($nome) {
        'Blockbuster Anos 80' => 'Blockbuster (entertainment)',
        'Neo-Noir' => 'Neo-noir',
        default => $termo,
    };

    $imagem = buscarImagemWikipedia($termo, true);

    if ($imagem === null && $termo !== $nome) {
        $imagem = buscarImagemWikipedia($nome, true);
    }

    if ($imagem === null) {
        $imagem = buscarImagemWikipedia($termo);
    }

    if ($imagem === null) {
        $imagem = $posterFallback($pdo, (int) $entidade['id']);
    }

    if ($imagem === null) {
        printf("  — %-28s sem imagem na Wikipédia nem obra de referência\n", $nome);
        continue;
    }

    $encontradas++;

    if ($dryRun) {
        printf("  + %-28s %s\n", $nome, $imagem);
    } else {
        $atualizar->execute(['imagem' => $imagem, 'id' => (int) $entidade['id']]);
        printf("  + %-28s atualizada\n", $nome);
    }

    usleep(350000);
}

printf(
    "\n%d imagem(ns) %s.\n",
    $encontradas,
    $dryRun ? 'encontrada(s) (nada gravado)' : 'gravada(s)'
);
