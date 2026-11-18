<?php

declare(strict_types=1);

/** Valida usuário/senha e inicia a sessão administrativa. */

require __DIR__ . '/_init.php';

use Core\Csrf;
use Core\RateLimit;
use Core\Session;
use Core\SessionUser;
use Models\UsuarioAdmin;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    pc_admin_redirect(url('admin/login.php'));
}

if (!Csrf::validarRequisicao()) {
    pc_admin_redirect(url('admin/login.php'), t('erros.csrf'), 'erro');
}

// Freio de força bruta: 8 tentativas a cada 10 minutos por IP.
$chaveLimite = 'login:' . (\Core\Http::ipHash() ?? 'anon');
$limite = RateLimit::permitir($chaveLimite, 8, 600);

if (!$limite['ok']) {
    $minutos = max(1, (int) ceil($limite['espera'] / 60));
    pc_admin_redirect(
        url('admin/login.php'),
        t('admin.too_many_attempts', ['minutos' => $minutos]),
        'erro'
    );
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

RateLimit::limpar($chaveLimite);
Session::regenerar();
SessionUser::registrar($autenticado);

pc_admin_redirect(url('admin/'), t('admin.logged_in'));
