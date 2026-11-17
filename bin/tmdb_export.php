<?php

declare(strict_types=1);

/**
 * Gera sql/003_enriquecimento.sql a partir do banco já enriquecido.
 *
 *   docker compose exec -T app php bin/tmdb_export.php > sql/003_enriquecimento.sql
 *
 * O arquivo é aplicado automaticamente pelo MySQL na primeira subida do Docker,
 * garantindo que um `down -v` continue com fotos, biografias e pôsteres.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;

$pdo = Database::pdo();

$q = static fn (?string $valor): string => $valor === null ? 'NULL' : $pdo->quote($valor);

echo "-- =============================================================================\n";
echo "-- PersonaCut — enriquecimento gerado pela TMDB\n";
echo '-- Gerado em ' . date('Y-m-d H:i:s') . "\n";
echo "-- Fonte: bin/tmdb_export.php (não edite à mão)\n";
echo "-- =============================================================================\n\n";
echo "SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n\n";
echo "START TRANSACTION;\n\n";

$entidades = $pdo->query(
    'SELECT * FROM entidades_match WHERE enriquecido_em IS NOT NULL ORDER BY tipo, nome'
)->fetchAll();

foreach ($entidades as $entidade) {
    $sets = [];

    if ($entidade['tmdb_id'] !== null) {
        $sets[] = 'tmdb_id = ' . (int) $entidade['tmdb_id'];
    }

    if ($entidade['imagem_url'] !== null) {
        $sets[] = 'imagem_url = ' . $q((string) $entidade['imagem_url']);
    }

    if ($entidade['ator_tmdb_id'] !== null) {
        $sets[] = 'ator_tmdb_id = ' . (int) $entidade['ator_tmdb_id'];
    }

    if ($entidade['ator_foto_url'] !== null) {
        $sets[] = 'ator_foto_url = ' . $q((string) $entidade['ator_foto_url']);
    }

    if ($entidade['pais'] !== null) {
        $sets[] = 'pais = ' . $q((string) $entidade['pais']);
    }

    if ($entidade['nascimento'] !== null) {
        $sets[] = 'nascimento = ' . (int) $entidade['nascimento'];
    }

    if ($entidade['popularidade'] !== null) {
        $sets[] = 'popularidade = ' . number_format((float) $entidade['popularidade'], 2, '.', '');
    }

    if ($sets === []) {
        continue;
    }

    $sets[] = 'enriquecido_em = ' . $q((string) ($entidade['enriquecido_em'] ?? date('Y-m-d H:i:s')));

    echo 'UPDATE entidades_match SET ' . implode(', ', $sets)
        . ' WHERE tipo = ' . $q((string) $entidade['tipo'])
        . ' AND nome = ' . $q((string) $entidade['nome']) . ";\n";
}

echo "\n";

$obras = $pdo->query(
    'SELECT o.titulo, o.tmdb_id, o.poster_url, e.tipo, e.nome
       FROM entidades_obras o
       JOIN entidades_match e ON e.id = o.entidade_id
      WHERE o.tmdb_id IS NOT NULL
      ORDER BY e.tipo, e.nome, o.ordem'
)->fetchAll();

foreach ($obras as $obra) {
    echo 'UPDATE entidades_obras o'
        . ' JOIN entidades_match e ON e.id = o.entidade_id'
        . ' SET o.tmdb_id = ' . (int) $obra['tmdb_id']
        . ', o.poster_url = ' . $q($obra['poster_url'] !== null ? (string) $obra['poster_url'] : null)
        . ' WHERE e.tipo = ' . $q((string) $obra['tipo'])
        . ' AND e.nome = ' . $q((string) $obra['nome'])
        . ' AND o.titulo = ' . $q((string) $obra['titulo']) . ";\n";
}

echo "\nCOMMIT;\n";
