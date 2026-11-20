<?php

declare(strict_types=1);

/** Entrar ou criar conta (opcional; necessária para bookmarks). */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Http;
use Core\RateLimit;

$pdo = Database::pdo();

$watchlistAtiva = (bool) ($config['features']['watchlist'] ?? false);
$cadastroAtivo  = (bool) ($config['features']['cadastro'] ?? false);

// Contas e lista pessoal pausadas: o site segue 100% anônimo.
if (!$watchlistAtiva) {
    $paginaTitulo = t('auth.paused_title') . ' — PersonaCut';
    $navSimples = true;

    require __DIR__ . '/partials/head.php';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="pc-section">
        <div class="pc-wrap pc-wrap--narrow">
            <span class="pc-eyebrow"><?= e(t('auth.eyebrow')) ?></span>
            <h1 class="pc-title"><?= e(t('auth.paused_title')) ?></h1>
            <p class="pc-lead pc-mt-2"><?= e(t('auth.paused_lead')) ?></p>
            <p class="pc-mt-3">
                <a class="pc-btn" href="<?= e(url('quiz.php')) ?>">
                    <?= e(t('auth.paused_cta')) ?>
                    <?= icon('arrow-right', 16) ?>
                </a>
            </p>
        </div>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$next = is_string($_REQUEST['next'] ?? null) ? trim((string) $_REQUEST['next']) : '';

if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '://')) {
    $next = '/minha-lista.php';
}

if (Auth::logado()) {
    header('Location: ' . $next);
    exit;
}

$erro = null;
$aba = 'login';

if (Http::metodo() === 'POST') {
    $aba = ($_POST['acao'] ?? 'login') === 'registrar' ? 'registrar' : 'login';

    if (!Csrf::validarRequisicao()) {
        $erro = t('erros.csrf');
    } else {
        $ip = Http::ipHash() ?? 'anon';
        $limite = RateLimit::permitir('login:' . $ip, 10, 300);

        if (!$limite['ok']) {
            $erro = t('auth.rate_limit', ['segundos' => $limite['espera']]);
        } elseif ($aba === 'registrar' && !$cadastroAtivo) {
            $erro = t('auth.register_disabled');
        } elseif ($aba === 'registrar') {
            $erro = Auth::registrar(
                $pdo,
                (string) ($_POST['nome'] ?? ''),
                (string) ($_POST['usuario'] ?? ''),
                (string) ($_POST['senha'] ?? '')
            );
        } else {
            $erro = Auth::entrar(
                $pdo,
                (string) ($_POST['usuario'] ?? ''),
                (string) ($_POST['senha'] ?? '')
            );
        }

        if ($erro === null) {
            RateLimit::limpar('login:' . $ip);
            header('Location: ' . $next);
            exit;
        }
    }
}

$paginaTitulo = t('auth.login') . ' — PersonaCut';
$navSimples = true;

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<section class="pc-section">
    <div class="pc-wrap pc-wrap--narrow">
        <span class="pc-eyebrow"><?= e(t('auth.eyebrow')) ?></span>
        <h1 class="pc-title"><?= e(t('auth.title')) ?></h1>
        <p class="pc-lead pc-mt-2"><?= e(t('auth.lead')) ?></p>

        <?php if ($erro !== null): ?>
            <div class="pc-alert pc-alert--erro pc-mt-3"><?= e($erro) ?></div>
        <?php endif; ?>

        <div class="pc-auth<?= $cadastroAtivo ? '' : ' pc-auth--single' ?> pc-mt-3">
            <section class="pc-auth__box<?= $aba === 'login' ? ' is-active' : '' ?>">
                <h2><?= e(t('auth.login_box')) ?></h2>
                <p><?= e(t('auth.login_help')) ?></p>

                <form method="post" action="<?= e(url('entrar.php')) ?>" class="pc-auth__form">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="login">
                    <input type="hidden" name="next" value="<?= e($next) ?>">

                    <label class="pc-field">
                        <span class="pc-label"><?= e(t('auth.user')) ?></span>
                        <input class="pc-input" type="text" name="usuario" autocomplete="username" required maxlength="40" placeholder="<?= e(t('auth.user_placeholder')) ?>" value="<?= e((string) ($_POST['usuario'] ?? '')) ?>">
                    </label>

                    <label class="pc-field">
                        <span class="pc-label"><?= e(t('auth.password')) ?></span>
                        <input class="pc-input" type="password" name="senha" autocomplete="current-password" required placeholder="<?= e(t('auth.password_placeholder')) ?>">
                    </label>

                    <button class="pc-btn" type="submit">
                        <?= e(t('auth.enter')) ?>
                        <?= icon('arrow-right', 16) ?>
                    </button>
                </form>

                <?php if (!$cadastroAtivo): ?>
                    <p class="pc-text-muted pc-mt-2"><?= e(t('auth.register_disabled')) ?></p>
                <?php endif; ?>
            </section>

            <?php if ($cadastroAtivo): ?>
            <section class="pc-auth__box<?= $aba === 'registrar' ? ' is-active' : '' ?>">
                <h2><?= e(t('auth.register_box')) ?></h2>
                <p><?= e(t('auth.register_help')) ?></p>

                <form method="post" action="<?= e(url('entrar.php')) ?>" class="pc-auth__form">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="registrar">
                    <input type="hidden" name="next" value="<?= e($next) ?>">

                    <label class="pc-field">
                        <span class="pc-label"><?= e(t('auth.name')) ?></span>
                        <input class="pc-input" type="text" name="nome" autocomplete="name" required maxlength="80" placeholder="<?= e(t('auth.name_placeholder')) ?>" value="<?= e((string) ($_POST['nome'] ?? '')) ?>">
                    </label>

                    <label class="pc-field">
                        <span class="pc-label"><?= e(t('auth.user')) ?></span>
                        <input class="pc-input" type="text" name="usuario" autocomplete="username" required maxlength="40" placeholder="<?= e(t('auth.user_placeholder')) ?>" pattern="[A-Za-z0-9._-]{3,30}" value="<?= e((string) ($_POST['usuario'] ?? '')) ?>">
                    </label>

                    <label class="pc-field">
                        <span class="pc-label"><?= e(t('auth.password')) ?></span>
                        <input class="pc-input" type="password" name="senha" autocomplete="new-password" required minlength="8" placeholder="<?= e(t('auth.password_new_placeholder')) ?>">
                        <small class="pc-text-muted"><?= e(t('auth.password_hint')) ?></small>
                    </label>

                    <button class="pc-btn pc-btn--ghost" type="submit">
                        <?= e(t('auth.create')) ?>
                        <?= icon('arrow-right', 16) ?>
                    </button>
                </form>
            </section>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
