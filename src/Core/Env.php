<?php

declare(strict_types=1);

namespace Core;

/**
 * Leitor de variáveis de ambiente com suporte opcional a config/env.php.
 */
final class Env
{
    /**
     * Carrega config/env.php (se existir) sem sobrescrever variáveis reais.
     */
    public static function carregar(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }

        /** @var array<string, string> $variaveis */
        $variaveis = require $arquivo;

        foreach ($variaveis as $chave => $valor) {
            if (getenv((string) $chave) === false) {
                putenv(sprintf('%s=%s', $chave, (string) $valor));
            }
        }
    }

    public static function texto(string $chave, ?string $padrao = null): string
    {
        $valor = getenv($chave);

        return ($valor === false || $valor === '') ? (string) $padrao : $valor;
    }

    public static function booleano(string $chave, bool $padrao = false): bool
    {
        $valor = getenv($chave);

        if ($valor === false || $valor === '') {
            return $padrao;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOL);
    }
}
