<?php

declare(strict_types=1);

/**
 * Gera o snapshot SQL do catálogo.
 *
 *   docker compose exec app php bin/catalogo_export.php
 *   docker compose exec app php bin/catalogo_export.php --saida=sql/010_catalogo_seed.sql
 *
 * O arquivo é um retrato completo (obras, gêneros, países e disponibilidade)
 * que pode ser importado depois de sql/009_catalogo.sql em uma instalação nova
 * ou no InfinityFree. É um SNAPSHOT: apaga e recria as linhas do catálogo, mas
 * NÃO toca em preço/ordem/ativa das plataformas (esses são editoriais e vivem
 * no painel).
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$argv = $argv ?? [];
$saida = dirname(__DIR__) . '/sql/010_catalogo_seed.sql';

foreach ($argv as $arg) {
    if (preg_match('/^--saida=(.+)$/', $arg, $m) === 1) {
        $saida = $m[1];

        if (!str_starts_with($saida, '/')) {
            $saida = dirname(__DIR__) . '/' . $saida;
        }
    }
}

$pdo = Database::pdo();

$valor = static function (mixed $v) use ($pdo): string {
    if ($v === null || $v === '') {
        return 'NULL';
    }

    return $pdo->quote((string) $v);
};

$inteiro = static fn (mixed $v): string => $v === null || $v === '' ? 'NULL' : (string) (int) $v;

$decimal = static function (mixed $v): string {
    if ($v === null || $v === '') {
        return 'NULL';
    }

    $texto = rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

    return $texto === '' || $texto === '-' ? 'NULL' : $texto;
};

$linhas = static function (string $sql) use ($pdo): array {
    return $pdo->query($sql)->fetchAll();
};

$obras = $linhas(
    'SELECT id, tmdb_id, tipo, titulo, titulo_original, ano, sinopse, duracao, temporadas,
            poster_path, backdrop_path, nota, votos, popularidade, idioma_original,
            eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8,
            confianca, ativo, atualizado_em
       FROM catalogo_obras
      ORDER BY id'
);

$generos = $linhas('SELECT id, slug, nome FROM catalogo_generos ORDER BY id');
$paises = $linhas('SELECT codigo, nome FROM catalogo_paises ORDER BY codigo');
$obraGeneros = $linhas('SELECT obra_id, genero_id FROM catalogo_obra_generos ORDER BY obra_id, genero_id');
$obraPaises = $linhas('SELECT obra_id, pais_codigo FROM catalogo_obra_paises ORDER BY obra_id, pais_codigo');
$pessoas = $linhas(
    'SELECT id, tmdb_id, nome, foto_path, popularidade FROM catalogo_pessoas ORDER BY id'
);
$creditos = $linhas(
    'SELECT obra_id, pessoa_id, funcao, personagem, ordem
       FROM catalogo_creditos ORDER BY obra_id, funcao, ordem'
);
$plataformas = $linhas(
    'SELECT tmdb_provider_id, slug, nome, logo_path, prioridade, ativa
       FROM catalogo_plataformas ORDER BY id'
);
$obraPlataformas = $linhas(
    'SELECT obra_id, plataforma_id, oferta, regiao
       FROM catalogo_obra_plataformas
      ORDER BY obra_id, plataforma_id, oferta'
);

$buffer = [];
$buffer[] = '-- =============================================================================';
$buffer[] = '-- PersonaCut — snapshot do catálogo audiovisual';
$buffer[] = '-- Gerado em ' . date('Y-m-d H:i:s') . ' por bin/catalogo_export.php';
$buffer[] = '-- NÃO EDITE À MÃO. Importe depois de sql/009_catalogo.sql.';
$buffer[] = '--';
$buffer[] = '-- Conteúdo: ' . count($obras) . ' obras, ' . count($generos) . ' gêneros, '
    . count($paises) . ' países, ' . count($pessoas) . ' pessoas, ' . count($creditos) . ' créditos, '
    . count($obraPlataformas) . ' vínculos de disponibilidade.';
$buffer[] = '-- =============================================================================';
$buffer[] = '';
$buffer[] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;';
$buffer[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$buffer[] = '';
$buffer[] = 'START TRANSACTION;';
$buffer[] = '';
$buffer[] = 'DELETE FROM catalogo_creditos;';
$buffer[] = 'DELETE FROM catalogo_pessoas;';
$buffer[] = 'DELETE FROM catalogo_obra_plataformas;';
$buffer[] = 'DELETE FROM catalogo_obra_generos;';
$buffer[] = 'DELETE FROM catalogo_obra_paises;';
$buffer[] = 'DELETE FROM catalogo_obras;';
$buffer[] = 'DELETE FROM catalogo_generos;';
$buffer[] = 'DELETE FROM catalogo_paises;';
$buffer[] = '';

/**
 * @param list<array<string, mixed>> $itens
 * @param list<string> $colunas
 * @param callable(array<string, mixed>): list<string> $mapear
 */
$escrever = static function (
    array &$buffer,
    string $tabela,
    array $colunas,
    array $itens,
    callable $mapear,
    int $tamanhoLote = 150
): void {
    foreach (array_chunk($itens, $tamanhoLote) as $lote) {
        $valores = [];

        foreach ($lote as $item) {
            $valores[] = '(' . implode(', ', $mapear($item)) . ')';
        }

        $buffer[] = 'INSERT INTO ' . $tabela . ' (' . implode(', ', $colunas) . ') VALUES';
        $buffer[] = implode(",\n", $valores) . ';';
        $buffer[] = '';
    }
};

$escrever(
    $buffer,
    'catalogo_generos',
    ['id', 'slug', 'nome'],
    $generos,
    static fn (array $g): array => [$inteiro($g['id']), $valor($g['slug']), $valor($g['nome'])]
);

$escrever(
    $buffer,
    'catalogo_paises',
    ['codigo', 'nome'],
    $paises,
    static fn (array $p): array => [$valor($p['codigo']), $valor($p['nome'])]
);

$escrever(
    $buffer,
    'catalogo_obras',
    [
        'id', 'tmdb_id', 'tipo', 'titulo', 'titulo_original', 'ano', 'sinopse', 'duracao', 'temporadas',
        'poster_path', 'backdrop_path', 'nota', 'votos', 'popularidade', 'idioma_original',
        'eixo_1', 'eixo_2', 'eixo_3', 'eixo_4', 'eixo_5', 'eixo_6', 'eixo_7', 'eixo_8',
        'confianca', 'ativo', 'atualizado_em',
    ],
    $obras,
    static fn (array $o): array => [
        $inteiro($o['id']), $inteiro($o['tmdb_id']), $valor($o['tipo']), $valor($o['titulo']),
        $valor($o['titulo_original']), $inteiro($o['ano']), $valor($o['sinopse']),
        $inteiro($o['duracao']), $inteiro($o['temporadas']), $valor($o['poster_path']),
        $valor($o['backdrop_path']), $decimal($o['nota']), $inteiro($o['votos']),
        $decimal($o['popularidade']), $valor($o['idioma_original']),
        $inteiro($o['eixo_1']), $inteiro($o['eixo_2']), $inteiro($o['eixo_3']), $inteiro($o['eixo_4']),
        $inteiro($o['eixo_5']), $inteiro($o['eixo_6']), $inteiro($o['eixo_7']), $inteiro($o['eixo_8']),
        $inteiro($o['confianca']), $inteiro($o['ativo']), $valor($o['atualizado_em']),
    ],
    80
);

$escrever(
    $buffer,
    'catalogo_obra_generos',
    ['obra_id', 'genero_id'],
    $obraGeneros,
    static fn (array $l): array => [$inteiro($l['obra_id']), $inteiro($l['genero_id'])],
    300
);

$escrever(
    $buffer,
    'catalogo_obra_paises',
    ['obra_id', 'pais_codigo'],
    $obraPaises,
    static fn (array $l): array => [$inteiro($l['obra_id']), $valor($l['pais_codigo'])],
    300
);

// Plataformas: preserva preço/ordem/ativa no destino; só atualiza nome e logo.
if ($plataformas !== []) {
    $valores = [];

    foreach ($plataformas as $p) {
        $valores[] = '(' . implode(', ', [
            $inteiro($p['tmdb_provider_id']),
            $valor($p['slug']),
            $valor($p['nome']),
            $valor($p['logo_path']),
            $inteiro($p['prioridade']),
            $inteiro($p['ativa']),
        ]) . ')';
    }

    $buffer[] = 'INSERT INTO catalogo_plataformas '
        . '(tmdb_provider_id, slug, nome, logo_path, prioridade, ativa) VALUES';
    $buffer[] = implode(",\n", $valores);
    $buffer[] = 'ON DUPLICATE KEY UPDATE nome = VALUES(nome), logo_path = VALUES(logo_path);';
    $buffer[] = '';
}

$escrever(
    $buffer,
    'catalogo_pessoas',
    ['id', 'tmdb_id', 'nome', 'foto_path', 'popularidade'],
    $pessoas,
    static fn (array $p): array => [
        $inteiro($p['id']), $inteiro($p['tmdb_id']), $valor($p['nome']),
        $valor($p['foto_path']), $decimal($p['popularidade']),
    ],
    200
);

$escrever(
    $buffer,
    'catalogo_creditos',
    ['obra_id', 'pessoa_id', 'funcao', 'personagem', 'ordem'],
    $creditos,
    static fn (array $c): array => [
        $inteiro($c['obra_id']), $inteiro($c['pessoa_id']), $valor($c['funcao']),
        $valor($c['personagem']), $inteiro($c['ordem']),
    ],
    300
);

$escrever(
    $buffer,
    'catalogo_obra_plataformas',
    ['obra_id', 'plataforma_id', 'oferta', 'regiao'],
    $obraPlataformas,
    static fn (array $l): array => [
        $inteiro($l['obra_id']), $inteiro($l['plataforma_id']), $valor($l['oferta']), $valor($l['regiao']),
    ],
    300
);

$buffer[] = 'COMMIT;';
$buffer[] = '';
$buffer[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$buffer[] = '';

$diretorio = dirname($saida);

if (!is_dir($diretorio)) {
    mkdir($diretorio, 0775, true);
}

$conteudo = implode("\n", $buffer);
$temporario = $saida . '.tmp';

if (file_put_contents($temporario, $conteudo) === false) {
    fwrite(STDERR, "Não foi possível gravar {$saida}\n");
    exit(1);
}

rename($temporario, $saida);

printf(
    "Snapshot gerado: %s (%.2f MB)\n%d obras · %d gêneros · %d países · %d pessoas · %d créditos · %d vínculos de plataforma\n",
    $saida,
    filesize($saida) / 1048576,
    count($obras),
    count($generos),
    count($paises),
    count($pessoas),
    count($creditos),
    count($obraPlataformas)
);
