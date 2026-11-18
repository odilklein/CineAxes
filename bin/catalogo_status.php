<?php

declare(strict_types=1);

/**
 * Diagnóstico do catálogo.
 *
 *   docker compose exec app php bin/catalogo_status.php
 *
 * Mostra o tamanho do catálogo, a cobertura por trilha e por plataforma, e
 * quantos títulos estão disponíveis por assinatura no Brasil. Serve para
 * decidir onde ampliar a curadoria (config/recomendacao.php).
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Models\CatalogoCredito;
use Models\CatalogoObra;
use Models\CatalogoPessoa;
use Models\Plataforma;
use Services\MatcherService;
use Services\RecomendacaoService;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$recConfig = require dirname(__DIR__) . '/config/recomendacao.php';
$pdo = Database::pdo();
$obras = CatalogoObra::todos($pdo);
$recomendacao = new RecomendacaoService($recConfig, new MatcherService());

$porTrilha = [];
$porPais = [];
$porTipo = ['filme' => 0, 'serie' => 0];
$semTrilha = 0;
$comPlataforma = 0;

foreach ($obras as $obra) {
    $tipo = (string) $obra['tipo'];
    $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + 1;

    if ($obra['plataformas'] !== []) {
        $comPlataforma++;
    }

    $slug = $recomendacao->trilhaDe($obra);

    if ($slug === null) {
        $semTrilha++;
    } else {
        $porTrilha[$slug] = ($porTrilha[$slug] ?? 0) + 1;
    }

    foreach ($obra['paises'] as $pais) {
        $codigo = (string) $pais['codigo'];
        $porPais[$codigo] = ($porPais[$codigo] ?? 0) + 1;
    }
}

arsort($porTrilha);
arsort($porPais);

$nomesTrilhas = array_map(
    static fn (array $trilha): string => (string) ($trilha['nome'] ?? ''),
    (array) ($recConfig['trilhas'] ?? [])
);

echo "\n  PersonaCut · catálogo\n";
echo str_repeat('─', 58) . "\n";
printf("  Obras: %d (%d filmes, %d séries)\n", count($obras), $porTipo['filme'] ?? 0, $porTipo['serie'] ?? 0);
printf("  Com assinatura no Brasil: %d\n", $comPlataforma);
printf("  Fora de trilha: %d\n", $semTrilha);
printf("  Pessoas: %d · créditos: %d\n\n", CatalogoPessoa::total($pdo), CatalogoCredito::total($pdo));

echo "  Por trilha\n";

foreach ($porTrilha as $slug => $total) {
    printf("    %-28s %5d\n", $nomesTrilhas[$slug] ?? $slug, $total);
}

echo "\n  Plataformas (assinatura)\n";

$porPlataforma = [];

foreach ($obras as $obra) {
    foreach ($obra['plataformas'] as $plataforma) {
        $id = (int) $plataforma['id'];
        $porPlataforma[$id] = ($porPlataforma[$id] ?? 0) + 1;
    }
}

foreach (Plataforma::todas($pdo) as $plataforma) {
    printf(
        "    %-22s %5d%s\n",
        $plataforma['nome'],
        $porPlataforma[(int) $plataforma['id']] ?? 0,
        (int) $plataforma['ativa'] === 1 ? '' : '  (inativa)'
    );
}

echo "\n  Países com mais títulos\n";

$indice = 0;

foreach ($porPais as $codigo => $total) {
    if ($indice++ >= 12) {
        break;
    }

    printf("    %-4s %5d\n", $codigo, $total);
}

echo "\n";
