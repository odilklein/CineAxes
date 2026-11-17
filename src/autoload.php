<?php

declare(strict_types=1);

/**
 * Autoload PSR-4 simples, sem Composer.
 *
 * Core\Foo        -> src/Core/Foo.php
 * Models\Foo      -> src/Models/Foo.php
 * Services\Foo    -> src/Services/Foo.php
 * Controllers\Foo -> src/Controllers/Foo.php
 */
spl_autoload_register(static function (string $classe): void {
    $prefixos = [
        'Core\\'        => __DIR__ . '/Core/',
        'Models\\'      => __DIR__ . '/Models/',
        'Services\\'    => __DIR__ . '/Services/',
        'Controllers\\' => __DIR__ . '/Controllers/',
    ];

    foreach ($prefixos as $prefixo => $diretorio) {
        if (!str_starts_with($classe, $prefixo)) {
            continue;
        }

        $relativo = substr($classe, strlen($prefixo));
        $arquivo  = $diretorio . str_replace('\\', '/', $relativo) . '.php';

        if (is_file($arquivo)) {
            require $arquivo;
            return;
        }
    }
});
