<?php

declare(strict_types=1);

namespace Core;

/**
 * Leitor de variáveis de ambiente com suporte opcional a config/env.php.
 *
 * Em hospedagem compartilhada o `putenv()` costuma ser desabilitado (ou virar
 * no-op), então os valores do arquivo ficam num mapa estático em vez de depender
 * do ambiente do processo. Variáveis reais continuam tendo prioridade.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $valores = [];

    /**
     * Carrega config/env.php (se existir) sem sobrescrever variáveis reais.
     */
    public static function carregar(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }

        $variaveis = require $arquivo;

        if (!is_array($variaveis)) {
            return;
        }

        foreach ($variaveis as $chave => $valor) {
            $chave = (string) $chave;

            if (getenv($chave) !== false) {
                continue;
            }

            self::$valores[$chave] = (string) $valor;
        }
    }

    public static function texto(string $chave, ?string $padrao = null): string
    {
        $valor = self::$valores[$chave] ?? getenv($chave);

        return ($valor === false || $valor === null || $valor === '')
            ? (string) $padrao
            : (string) $valor;
    }

    public static function booleano(string $chave, bool $padrao = false): bool
    {
        $valor = self::$valores[$chave] ?? getenv($chave);

        if ($valor === false || $valor === null || $valor === '') {
            return $padrao;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOL);
    }
}
