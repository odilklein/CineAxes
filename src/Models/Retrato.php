<?php

declare(strict_types=1);

namespace Models;

use PDO;

final class Retrato
{
    /** @return array<string, mixed>|null */
    public static function porCodigo(PDO $pdo, string $codigo): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT codigo_unico, idioma, texto, modelo, prompt_versao, tokens, created_at
               FROM retratos
              WHERE codigo_unico = :codigo
              LIMIT 1'
        );
        $stmt->execute(['codigo' => strtoupper(trim($codigo))]);
        $linha = $stmt->fetch();

        return $linha === false ? null : $linha;
    }

    /** @param array<string, mixed> $dados */
    public static function criar(PDO $pdo, array $dados): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO retratos (codigo_unico, idioma, texto, modelo, prompt_versao, tokens)
             VALUES (:codigo, :idioma, :texto, :modelo, :prompt_versao, :tokens)
             ON DUPLICATE KEY UPDATE id = id'
        );
        $stmt->execute([
            'codigo' => $dados['codigo'],
            'idioma' => $dados['idioma'] ?? 'pt-BR',
            'texto' => $dados['texto'],
            'modelo' => $dados['modelo'],
            'prompt_versao' => $dados['prompt_versao'],
            'tokens' => $dados['tokens'] ?? null,
        ]);
    }
}
