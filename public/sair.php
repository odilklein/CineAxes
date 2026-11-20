<?php

declare(strict_types=1);

/** Encerra a conta pública. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Auth;
use Core\Csrf;
use Core\Http;

if (Http::metodo() !== 'POST') {
    header('Location: ' . url('/'));
    exit;
}

if (Csrf::validarRequisicao()) {
    Auth::sair();
}

header('Location: ' . url('/'));
exit;
