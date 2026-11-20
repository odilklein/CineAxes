<?php

declare(strict_types=1);

/** Login do painel administrativo. */

require __DIR__ . '/_init.php';

use Core\Csrf;
use Core\Session;
use Core\SessionUser;

if (SessionUser::atual() !== null) {
    pc_admin_redirect(url('admin/'));
}

$erro = Session::flash('erro');
?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(t('admin.login_title')) ?> — PersonaCut</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <script src="<?= asset('js/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('vendor/vuexy/core.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/personacut.css') ?>">
</head>
<body>
<main class="pc-login">
    <div class="pc-login__card">
        <a class="pc-logo" href="<?= url('/') ?>">
            Persona<span>Cut</span><i class="pc-logo__dot" aria-hidden="true"></i>
        </a>

        <h1 class="pc-title pc-mt-3" style="font-size:28px"><?= e(t('admin.login_title')) ?></h1>
        <p class="pc-text-muted pc-mt-1" style="font-size:13.5px"><?= e(t('admin.login_subtitle')) ?></p>

        <?php if ($erro !== null): ?>
            <div class="pc-alert pc-alert--erro pc-mt-3"><?= e($erro) ?></div>
        <?php endif; ?>

        <form class="pc-mt-3" action="<?= url('admin/auth.php') ?>" method="post" style="display:grid;gap:14px">
            <?= Csrf::campo() ?>

            <div class="pc-field">
                <label class="pc-label" for="usuario"><?= e(t('admin.user')) ?></label>
                <input class="pc-input" id="usuario" name="usuario" type="text" autocomplete="username" required autofocus placeholder="seu_usuario"
                       style="font-family:var(--pc-font);letter-spacing:0;text-transform:none">
            </div>

            <div class="pc-field">
                <label class="pc-label" for="senha"><?= e(t('admin.password')) ?></label>
                <input class="pc-input" id="senha" name="senha" type="password" autocomplete="current-password" required placeholder="Sua senha"
                       style="font-family:var(--pc-font);letter-spacing:0;text-transform:none">
            </div>

            <button class="pc-btn pc-btn--block" type="submit">
                <?= e(t('admin.login')) ?>
                <?= icon('arrow-right', 17) ?>
            </button>
        </form>

        <p class="pc-hint pc-text-center"><?= e(t('admin.login_subtitle')) ?></p>
    </div>
</main>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
