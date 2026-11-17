<?php

declare(strict_types=1);

/** Valida usuário/senha e inicia a sessão administrativa. */

require __DIR__ . '/_init.php';

use Core\Csrf;
use Core\Session;
use Core\SessionUser;
use Models\UsuarioAdmin;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    pc_admin_redirect(url('admin/login.php'));
}

if (!Csrf::validarRequisicao()) {
    pc_admin_redirect(url('admin/login.php'), t('erros.csrf'), 'erro');
}

$usuario = pc_admin_texto('usuario');
$senha = (string) ($_POST['senha'] ?? '');

if ($usuario === '' || $senha === '') {
    pc_admin_redirect(url('admin/login.php'), t('admin.wrong_credentials'), 'erro');
}

$autenticado = UsuarioAdmin::autenticar($pdo, $usuario, $senha);

if ($autenticado === null) {
    // Mensagem genérica: não revela se o usuário existe.
    pc_admin_redirect(url('admin/login.php'), t('admin.wrong_credentials'), 'erro');
}

Session::regenerar();
SessionUser::registrar($autenticado);

pc_admin_redirect(url('admin/'), t('admin.logged_in'));
