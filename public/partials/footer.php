<?php

declare(strict_types=1);

/**
 * Fechamento do documento.
 *
 * Variáveis opcionais:
 * - $rodapeSimples (bool): usado no quiz (só a linha de base).
 * - $scriptsExtras (list<string>): caminhos extras em assets/js.
 */

$rodapeSimples = $rodapeSimples ?? false;
$scriptsExtras = $scriptsExtras ?? [];
$vendorScripts = $vendorScripts ?? [];
?>
</main>

<?php if (!$rodapeSimples): ?>
<footer class="pc-footer">
    <div class="pc-wrap">
        <div class="pc-footer__grid">
            <div class="pc-footer__brand">
                <a class="pc-logo" href="<?= url('/') ?>" style="color:#f4efe2">
                    Persona<span style="color:#f4efe2">Cut</span><i class="pc-logo__dot" aria-hidden="true"></i>
                </a>
                <p><?= e(t('home.footer.tagline')) ?></p>
                <p style="margin-top:10px;font-size:11.5px;opacity:.5"><?= e(t('ui.made_with')) ?></p>
            </div>

            <div>
                <h4><?= e(t('home.footer.project')) ?></h4>
                <ul>
                    <li><a href="<?= url('/') ?>#como-funciona"><?= e(t('ui.nav.how')) ?></a></li>
                    <li><a href="<?= url('/') ?>#eixos"><?= e(t('ui.nav.axes')) ?></a></li>
                    <li><a href="<?= url('/') ?>#formatos"><?= e(t('ui.nav.formats')) ?></a></li>
                    <li><a href="<?= url('/') ?>#comparar"><?= e(t('ui.nav.compare')) ?></a></li>
                </ul>
            </div>

            <div>
                <h4><?= e(t('home.footer.explore')) ?></h4>
                <ul>
                    <li><a href="<?= url('quiz.php') ?>"><?= e(t('ui.nav.start')) ?></a></li>
                    <li><a href="<?= url('quiz.php') ?>"><?= e(t('home.hero.cta_primary')) ?></a></li>
                    <li><a href="<?= url('admin/login.php') ?>"><?= e(t('ui.nav.admin')) ?></a></li>
                </ul>
            </div>
        </div>

        <div class="pc-footer__base">
            <span>© <?= date('Y') ?> PersonaCut. <?= e(t('home.footer.credits')) ?></span>
            <span><?= e(t('ui.source')) ?>: PHP 8 · MySQL 8 · Bootstrap 5</span>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="<?= asset('vendor/vuexy/bootstrap.bundle.js') ?>"></script>
<?php foreach ($vendorScripts as $vendor): ?>
<script src="<?= asset('vendor/' . $vendor) ?>"></script>
<?php endforeach; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach ($scriptsExtras as $script): ?>
<script src="<?= asset('js/' . $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
