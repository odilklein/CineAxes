<?php

declare(strict_types=1);

/**
 * Micro-framework de testes (sem PHPUnit, para rodar com php puro).
 *
 * Uso:
 *   php tests/run.php
 */

$GLOBALS['pc_testes'] = [
    'total'  => 0,
    'ok'     => 0,
    'falhas' => [],
];

function teste(string $nome, callable $corpo): void
{
    $GLOBALS['pc_testes']['total']++;

    try {
        $corpo();
        $GLOBALS['pc_testes']['ok']++;
        echo "  \033[32m✓\033[0m {$nome}\n";
    } catch (Throwable $erro) {
        $GLOBALS['pc_testes']['falhas'][] = $nome . ': ' . $erro->getMessage();
        echo "  \033[31m✗\033[0m {$nome}\n";
        echo "      " . $erro->getMessage() . "\n";
    }
}

function afirmar(bool $condicao, string $mensagem = 'condição falsa'): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

function igual(mixed $esperado, mixed $obtido, string $mensagem = ''): void
{
    if ($esperado !== $obtido) {
        throw new RuntimeException(sprintf(
            '%s esperado %s, obtido %s',
            $mensagem !== '' ? $mensagem . ':' : '',
            var_export($esperado, true),
            var_export($obtido, true)
        ));
    }
}

function proximo(float $esperado, float $obtido, float $tolerancia = 0.01, string $mensagem = ''): void
{
    if (abs($esperado - $obtido) > $tolerancia) {
        throw new RuntimeException(sprintf(
            '%s esperado %.2f, obtido %.2f (tolerância %.2f)',
            $mensagem !== '' ? $mensagem . ':' : '',
            $esperado,
            $obtido,
            $tolerancia
        ));
    }
}

function resumoTestes(): int
{
    $dados = $GLOBALS['pc_testes'];

    echo "\n";
    echo str_repeat('─', 54) . "\n";
    printf("  %d testes · %d ok · %d falha(s)\n", $dados['total'], $dados['ok'], count($dados['falhas']));

    if ($dados['falhas'] !== []) {
        echo "\n  Falhas:\n";

        foreach ($dados['falhas'] as $falha) {
            echo '   - ' . $falha . "\n";
        }

        return 1;
    }

    echo "  \033[32mTudo certo.\033[0m\n";

    return 0;
}
