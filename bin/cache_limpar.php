<?php

declare(strict_types=1);

/**
 * Limpeza de cache e manutenção leve do catálogo.
 *
 *   docker compose exec app php bin/cache_limpar.php
 *   docker compose exec app php bin/cache_limpar.php --dias=2
 *
 * `cache_tmdb` guarda payloads grandes (detalhes + watch/providers). Sem
 * limpeza, o banco local passa de 100 MB em poucos meses. O TTL de 24h já
 * ignora linhas vencidas na leitura; esta rotina apaga o que já venceu.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$argv = $argv ?? [];
$dias = 7;
$tudo = in_array('--tudo', $argv, true);

foreach ($argv as $arg) {
    if (preg_match('/^--dias=(\d+)$/', $arg, $m) === 1) {
        $dias = max(0, (int) $m[1]);
    }
}

$pdo = Database::pdo();
$limite = date('Y-m-d H:i:s', time() - $dias * 86400);

if ($tudo) {
    $removidas = $pdo->exec('DELETE FROM cache_tmdb');
    printf("cache_tmdb: %d linha(s) removida(s) (cache limpo por inteiro).\n", (int) $removidas);
} else {
    $stmt = $pdo->prepare('DELETE FROM cache_tmdb WHERE expires_at < :limite');
    $stmt->execute(['limite' => $limite]);

    printf(
        "cache_tmdb: %d linha(s) vencida(s) removida(s) (antes de %s).\n",
        $stmt->rowCount(),
        $limite
    );
}

$tamanho = $pdo->query(
    "SELECT ROUND((data_length + index_length) / 1024 / 1024, 1)
       FROM information_schema.tables
      WHERE table_schema = DATABASE() AND table_name = 'cache_tmdb'"
)->fetchColumn();

printf("Tamanho atual de cache_tmdb: %s MB\n", (string) $tamanho);
