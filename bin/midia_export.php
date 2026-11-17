<?php

declare(strict_types=1);

/**
 * Gera sql/004_cenas_video.sql a partir do banco já preenchido.
 *
 *   docker compose exec -T app php bin/midia_export.php > sql/004_cenas_video.sql
 *
 * O arquivo roda depois do seed em uma instalação nova, preservando as cenas
 * em vídeo mesmo depois de um `docker compose down -v`.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;

$pdo = Database::pdo();
$linhas = $pdo->query(
    "SELECT texto_key, midia_tipo, midia_url_ou_id, midia_meta
       FROM perguntas
      WHERE midia_tipo IN ('video_yt', 'gif')
      ORDER BY ordem, id"
)->fetchAll();

echo "-- =============================================================================\n";
echo "-- PersonaCut — cenas em vídeo (gerado a partir do banco)\n";
echo "-- Gerado em " . date('Y-m-d H:i:s') . "\n";
echo "-- Fonte: bin/midia_export.php (não edite à mão)\n";
echo "-- =============================================================================\n\n";
echo "SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n\n";
echo "START TRANSACTION;\n\n";

foreach ($linhas as $linha) {
    $tipo = (string) $linha['midia_tipo'];
    $meta = json_encode(
        json_decode((string) $linha['midia_meta'], true),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    echo "UPDATE perguntas\n";
    echo '   SET midia_tipo = ' . $pdo->quote($tipo) . ",\n";
    echo '       midia_url_ou_id = ' . $pdo->quote((string) $linha['midia_url_ou_id']) . ",\n";
    echo '       midia_meta = ' . $pdo->quote((string) $meta) . "\n";
    echo ' WHERE texto_key = ' . $pdo->quote((string) $linha['texto_key']) . ";\n\n";
}

echo "COMMIT;\n";
