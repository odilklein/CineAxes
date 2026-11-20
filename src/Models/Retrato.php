<?php

declare(strict_types=1);

namespace Models;

use PDO;

final class Retrato
{
    /**
     * Retrato salvo do código. Quando `$versao` é informada, um texto de outra
     * versão do prompt é tratado como inexistente (para ser regerado).
     *
     * @return array<string, mixed>|null
     */
    public static function porCodigo(PDO $pdo, string $codigo, ?string $versao = null): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT codigo_unico, idioma, texto, frase, narrativa_json, modelo, prompt_versao, tokens, created_at
               FROM retratos
              WHERE codigo_unico = :codigo
              LIMIT 1'
        );
        $stmt->execute(['codigo' => strtoupper(trim($codigo))]);
        $linha = $stmt->fetch();

        if ($linha === false) {
            return null;
        }

        if ($versao !== null && (string) $linha['prompt_versao'] !== $versao) {
            return null;
        }

        $narrativa = $linha['narrativa_json'] ?? null;
        $linha['narrativa'] = is_string($narrativa) && $narrativa !== ''
            ? (json_decode($narrativa, true) ?: null)
            : null;
        unset($linha['narrativa_json']);

        return $linha;
    }

    /** @param array<string, mixed> $dados */
    public static function criar(PDO $pdo, array $dados): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO retratos (codigo_unico, idioma, texto, frase, narrativa_json, modelo, prompt_versao, tokens)
             VALUES (:codigo, :idioma, :texto, :frase, :narrativa, :modelo, :prompt_versao, :tokens)
             ON DUPLICATE KEY UPDATE
                idioma = VALUES(idioma),
                texto = VALUES(texto),
                frase = VALUES(frase),
                narrativa_json = VALUES(narrativa_json),
                modelo = VALUES(modelo),
                prompt_versao = VALUES(prompt_versao),
                tokens = VALUES(tokens),
                created_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'codigo' => $dados['codigo'],
            'idioma' => $dados['idioma'] ?? 'pt-BR',
            'texto' => $dados['texto'],
            'frase' => $dados['frase'] ?? null,
            'narrativa' => isset($dados['narrativa'])
                ? json_encode($dados['narrativa'], JSON_UNESCAPED_UNICODE)
                : null,
            'modelo' => $dados['modelo'],
            'prompt_versao' => $dados['prompt_versao'],
            'tokens' => $dados['tokens'] ?? null,
        ]);
    }
}
