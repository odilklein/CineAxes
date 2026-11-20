<?php

declare(strict_types=1);

/**
 * Cabeçalho do site.
 *
 * Variáveis: $navSimples (bool) — usado no quiz, esconde os links de navegação
 * e troca o CTA por "Recomeçar".
 */

$navSimples = $navSimples ?? false;

use Core\Auth;
use Core\Csrf;
?>
<header class="pc-header">
    <div class="pc-wrap pc-header__inner">
        <a class="pc-logo" href="<?= url('/') ?>" aria-label="PersonaCut — início">
            Persona<span>Cut</span><i class="pc-logo__dot" aria-hidden="true"></i>
        </a>

        <?php if (!$navSimples): ?>
            <nav class="pc-nav" id="pc-nav" aria-label="Navegação principal">
                <a href="<?= url('/') ?>#como-funciona"><?= e(t('ui.nav.how')) ?></a>
                <a href="<?= url('/') ?>#eixos"><?= e(t('ui.nav.axes')) ?></a>
                <a href="<?= url('/') ?>#formatos"><?= e(t('ui.nav.formats')) ?></a>
                <a href="<?= url('/') ?>#comparar"><?= e(t('ui.nav.compare')) ?></a>
            </nav>
        <?php endif; ?>

        <div class="pc-header__actions">
            <?php if ($config['features']['watchlist'] ?? false): ?>
            <div class="pc-header__auth">
                <?php if (Auth::logado()): ?>
                    <a class="pc-header__link" href="<?= e(url('minha-lista.php')) ?>"><?= e(t('auth.my_list')) ?></a>
                    <span class="pc-header__user"><?= e(Auth::nome()) ?></span>
                    <form class="pc-header__logout" action="<?= e(url('sair.php')) ?>" method="post">
                        <?= Csrf::campo() ?>
                        <button type="submit"><?= e(t('auth.logout')) ?></button>
                    </form>
                <?php else: ?>
                    <a class="pc-header__link" href="<?= e(url('entrar.php')) ?>"><?= e(t('auth.login')) ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <button class="pc-theme" type="button" data-theme-toggle aria-label="<?= e(t('ui.nav.theme')) ?>">
                <?= icon('moon', 18, 'pc-ico--moon') ?>
                <?= icon('sun', 18, 'pc-ico--sun') ?>
            </button>

            <button class="pc-menu-btn" type="button" data-menu-toggle aria-controls="pc-nav" aria-expanded="false" aria-label="<?= e(t('ui.nav.menu')) ?>">
                <?= icon('list', 20) ?>
            </button>

            <?php if ($navSimples): ?>
                <a class="pc-btn pc-btn--sm pc-btn--ghost" href="<?= url('/') ?>">
                    <?= e(t('ui.back_home')) ?>
                </a>
            <?php else: ?>
                <a class="pc-btn pc-btn--sm" href="<?= url('quiz.php') ?>">
                    <?= e(t('ui.nav.start')) ?>
                    <?= icon('arrow-right', 17) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main id="conteudo">
