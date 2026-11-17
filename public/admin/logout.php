<?php

declare(strict_types=1);

/** Encerra a sessão administrativa (somente POST + CSRF). */

require __DIR__ . '/_init.php';

use Core\Csrf;
use Core\Session;
use Core\SessionUser;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !Csrf::validarRequisicao()) {
    pc_admin_redirect(url('/'));
}

SessionUser::sair();
Session::regenerar();

pc_admin_redirect(url('/'), t('admin.logged_out'));
