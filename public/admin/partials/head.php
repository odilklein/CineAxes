<?php

declare(strict_types=1);

/**
 * Abertura do layout do painel.
 *
 * Variáveis: $adminTitulo, $adminAtivo, $admin (vem de _guard.php), $config.
 */

use Core\Session;

$adminTitulo = $adminTitulo ?? 'Painel';
$adminAtivo = $adminAtivo ?? '';
$flashErro = Session::flash('erro');
$flashOk = Session::flash('ok');
$itensNav = [
    ''            => t('admin.dashboard'),
    'eixos.php'   => t('admin.axes'),
    'perguntas.php' => t('admin.questions'),
    'entidades.php' => t('admin.entities'),
    'arquetipos.php' => t('admin.archetypes'),
    'plataformas.php' => t('admin.platforms'),
];
?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitulo) ?> — <?= e($config['nome']) ?></title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <script src="<?= asset('js/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('vendor/vuexy/core.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/personacut.css') ?>">
</head>
<body class="pc-admin">
<header class="pc-admin__bar">
    <a class="pc-logo" href="<?= url('admin/') ?>">
        Persona<span>Cut</span><i class="pc-logo__dot" aria-hidden="true"></i>
    </a>

    <nav class="pc-admin__nav" aria-label="Painel">
        <?php foreach ($itensNav as $arquivo => $rotulo): ?>
            <a href="<?= url('admin/' . $arquivo) ?>" class="<?= $adminAtivo === $arquivo ? 'is-active' : '' ?>">
                <?= e($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="pc-admin__actions">
        <button class="pc-theme" type="button" data-theme-toggle aria-label="<?= e(t('ui.nav.theme')) ?>">
            <?= icon('moon', 18, 'pc-ico--moon') ?>
            <?= icon('sun', 18, 'pc-ico--sun') ?>
        </button>

        <span class="pc-admin__user"><?= e((string) ($admin['nome'] ?? '')) ?></span>
        <a class="pc-btn pc-btn--sm pc-btn--ghost" href="<?= url('/') ?>"><?= e(t('admin.view_public')) ?></a>

        <form action="<?= url('admin/logout.php') ?>" method="post" style="margin:0">
            <?= \Core\Csrf::campo() ?>
            <button class="pc-btn pc-btn--sm pc-btn--light" type="submit"><?= e(t('admin.logout')) ?></button>
        </form>
    </div>
</header>

<main class="pc-admin__main">
    <?php if ($flashErro !== null): ?>
        <div class="pc-alert pc-alert--erro pc-mb-3"><?= e($flashErro) ?></div>
    <?php endif; ?>

    <?php if ($flashOk !== null): ?>
        <div class="pc-alert pc-alert--ok pc-mb-3"><?= e($flashOk) ?></div>
    <?php endif; ?>
