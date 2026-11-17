<?php

declare(strict_types=1);

/**
 * GET /api/imagem.php?u=https://image.tmdb.org/t/p/w500/abc.jpg
 *
 * Proxy de imagens da TMDB com cache local e CORS. Necessário para o
 * html2canvas exportar o card de compartilhamento sem contaminar o canvas.
 */

$url = is_string($_GET['u'] ?? null) ? (string) $_GET['u'] : '';

if (!preg_match('#^https://image\.tmdb\.org/t/p/[a-zA-Z0-9_]+/[a-zA-Z0-9._-]+$#', $url)) {
    http_response_code(400);
    exit;
}

$dir = dirname(__DIR__, 2) . '/storage/cache/img';

if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

$arquivo = $dir . '/' . hash('sha256', $url);

if (!is_file($arquivo)) {
    $curl = curl_init($url);

    if ($curl === false) {
        http_response_code(502);
        exit;
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'PersonaCut/1.0',
    ]);

    $corpo = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($corpo === false || $status !== 200) {
        http_response_code(404);
        exit;
    }

    @file_put_contents($arquivo, $corpo);
}

$info = @getimagesize($arquivo);
$tipo = is_array($info) && isset($info['mime']) ? (string) $info['mime'] : 'image/jpeg';

header('Content-Type: ' . $tipo);
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400');
readfile($arquivo);
