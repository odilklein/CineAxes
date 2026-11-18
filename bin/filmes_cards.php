<?php

declare(strict_types=1);

/**
 * Resolve pôsteres dos filmes citados nas leituras e no guia dos eixos.
 *
 *   docker compose exec app php bin/filmes_cards.php
 *   docker compose exec app php bin/filmes_cards.php --force
 *
 * Grava config/filmes_cards.php (versionado), para que a página de resultado
 * monte os cards sem nenhuma chamada de rede. Filmes não encontrados ficam de
 * fora do mapa e o front usa um card tipográfico.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Services\TmdbService;

$base = dirname(__DIR__);
$destino = $base . '/config/filmes_cards.php';
$forcar = in_array('--force', $argv ?? [], true);

$tmdb = new TmdbService(Database::pdo(), $config['tmdb']);

if (!$tmdb->disponivel()) {
    fwrite(STDERR, "TMDB_API_KEY ausente. Configure no .env e recrie o container.\n");
    exit(1);
}

/** @var array<string, array{titulo: string, ano: int|null}> $alvos */
$alvos = [];

$registrar = static function (string $titulo, mixed $ano) use (&$alvos): void {
    $titulo = trim($titulo);

    if ($titulo === '') {
        return;
    }

    $anoInt = is_numeric($ano) ? (int) $ano : null;
    $chave = mb_strtolower($titulo) . '|' . ($anoInt ?? '');

    if (!isset($alvos[$chave])) {
        $alvos[$chave] = ['titulo' => $titulo, 'ano' => $anoInt];
    }
};

$leituras = require $base . '/config/leituras.php';

foreach (($leituras['eixos'] ?? []) as $eixo) {
    foreach (['e', 'd'] as $polo) {
        foreach (($eixo[$polo]['combina'] ?? []) as $filme) {
            $registrar((string) ($filme['titulo'] ?? ''), $filme['ano'] ?? null);
        }
    }
}

foreach (($leituras['neutro']['combina'] ?? []) as $filme) {
    $registrar((string) ($filme['titulo'] ?? ''), $filme['ano'] ?? null);
}

/** @var array<string, array<string, mixed>> $guias */
$guias = require $base . '/src/Data/eixos_guia.php';

foreach ($guias as $guia) {
    foreach (($guia['cenas'] ?? []) as $cena) {
        if (($cena['tipo'] ?? 'filme') !== 'filme') {
            continue;
        }

        $registrar((string) ($cena['obra'] ?? ''), $cena['ano'] ?? null);
    }
}

/** @var array<int, array<string, mixed>> $decadas */
$decadas = require $base . '/src/Data/decadas.php';

foreach ($decadas as $decada) {
    foreach (($decada['marcos'] ?? []) as $filme) {
        $registrar((string) ($filme['titulo'] ?? ''), $filme['ano'] ?? null);
    }
}

$anteriores = is_file($destino) ? (require $destino) : [];
$mapa = is_array($anteriores) ? $anteriores : [];
$novos = 0;
$falhas = [];
$total = count($alvos);
$i = 0;

foreach ($alvos as $chave => $alvo) {
    $i++;

    if (!$forcar && isset($mapa[$chave])) {
        continue;
    }

    $encontrado = $tmdb->procurarFilme($alvo['titulo'], $alvo['ano']);

    // Títulos muito longos (com subtítulo, lista de coadjuvantes) às vezes não
    // retornam nada: tenta a parte antes dos dois-pontos e da primeira vírgula.
    if ($encontrado === null) {
        $curtos = array_filter([
            trim(explode(':', $alvo['titulo'])[0]),
            trim(explode(',', $alvo['titulo'])[0]),
        ], static fn (string $t): bool => $t !== '' && $t !== $alvo['titulo']);

        foreach ($curtos as $curto) {
            $encontrado = $tmdb->procurarFilme($curto, $alvo['ano']);

            if ($encontrado !== null) {
                break;
            }
        }
    }

    if ($encontrado === null) {
        $falhas[] = $alvo['titulo'] . ($alvo['ano'] !== null ? ' (' . $alvo['ano'] . ')' : '');
        continue;
    }

    $poster = $tmdb->imagem($encontrado['poster_path'] ?? null, 'w342');

    $mapa[$chave] = [
        'tmdb'   => (int) ($encontrado['id'] ?? 0),
        'titulo' => (string) ($encontrado['title'] ?? $alvo['titulo']),
        'ano'    => isset($encontrado['release_date']) && $encontrado['release_date'] !== ''
            ? (int) substr((string) $encontrado['release_date'], 0, 4)
            : $alvo['ano'],
        'poster' => $poster,
    ];

    $novos++;

    fwrite(STDOUT, sprintf("  ✓ %s → %s\n", $alvo['titulo'], $mapa[$chave]['titulo']));
}

ksort($mapa);

$php = "<?php\n\ndeclare(strict_types=1);\n\n"
    . "/**\n"
    . " * Pôsteres dos filmes citados nas leituras e no guia dos eixos.\n"
    . " *\n"
    . " * GERADO por bin/filmes_cards.php — não edite à mão.\n"
    . " * Chave: título em minúsculas + \"|\" + ano.\n"
    . " */\n\n"
    . 'return ' . var_export($mapa, true) . ";\n";

file_put_contents($destino, $php);

fwrite(STDOUT, sprintf(
    "\n%d títulos, %d novos, %d no mapa, %d não encontrados.\n%s\n",
    $total,
    $novos,
    count($mapa),
    count($falhas),
    $falhas !== [] ? 'Sem correspondência: ' . implode(' · ', $falhas) : ''
));
