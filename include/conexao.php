<?php
// Conexão PDO com o MySQL.
// As variáveis de ambiente vêm do docker-compose.yml; os valores após "?:" são o padrão local.

$host   = getenv('DB_HOST') ?: 'db';
$port   = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'cineaxes';
$user   = getenv('DB_USER') ?: 'cineaxes';
$pass   = getenv('DB_PASS') ?: 'cineaxes123';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    // Erros viram exceções (facilita o debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco: " . htmlspecialchars($e->getMessage()));
}
