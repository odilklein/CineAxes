<?php

declare(strict_types=1);

/**
 * Ações do pôster: quero ver (bookmark) e já vi (olho).
 *
 * Espera as variáveis $bmTmdb, $bmTipo, $bmTitulo, $bmAno, $bmPoster,
 * $bmEstado e $bmAuth. $bmInline (opcional) mostra os ícones no fluxo do
 * texto, em vez de sobrepostos à imagem.
 */

// Recurso desligado: nenhum botão é renderizado (FEATURE_WATCHLIST=false).
if (!($config['features']['watchlist'] ?? false)) {
    return;
}

$bmTmdb = (int) ($bmTmdb ?? 0);
$bmTitulo = trim((string) ($bmTitulo ?? ''));

if ($bmTmdb <= 0 || $bmTitulo === '') {
    return;
}

$bmTipo = (string) ($bmTipo ?? 'filme');
$bmAno = $bmAno ?? null;
$bmPoster = (string) ($bmPoster ?? '');
$bmEstado = in_array($bmEstado ?? null, ['quero', 'visto'], true) ? $bmEstado : null;
$bmAuth = (bool) ($bmAuth ?? false);
$bmInline = (bool) ($bmInline ?? false);
$bmClasse = 'pc-poster-actions' . ($bmInline ? ' pc-poster-actions--inline' : '');
?>
<div class="<?= e($bmClasse) ?>">
    <button class="btn btn-icon rounded-circle pc-poster-action<?= $bmEstado === 'quero' ? ' is-active' : '' ?>"
            type="button"
            data-bookmark
            data-status="quero"
            data-tmdb="<?= $bmTmdb ?>"
            data-tipo="<?= e($bmTipo) ?>"
            data-titulo="<?= e($bmTitulo) ?>"
            data-ano="<?= $bmAno !== null ? (int) $bmAno : '' ?>"
            data-poster="<?= e($bmPoster) ?>"
            data-auth="<?= $bmAuth ? '1' : '0' ?>"
            aria-pressed="<?= $bmEstado === 'quero' ? 'true' : 'false' ?>"
            aria-label="<?= e(t('auth.mark_want')) ?>"
            title="<?= e(t('auth.mark_want')) ?>">
        <span class="pc-poster-action__off"><?= icon('bookmark-plus', 17) ?></span>
        <span class="pc-poster-action__on"><?= icon('bookmark-check', 17) ?></span>
    </button>

    <button class="btn btn-icon rounded-circle pc-poster-action<?= $bmEstado === 'visto' ? ' is-active' : '' ?>"
            type="button"
            data-bookmark
            data-status="visto"
            data-tmdb="<?= $bmTmdb ?>"
            data-tipo="<?= e($bmTipo) ?>"
            data-titulo="<?= e($bmTitulo) ?>"
            data-ano="<?= $bmAno !== null ? (int) $bmAno : '' ?>"
            data-poster="<?= e($bmPoster) ?>"
            data-auth="<?= $bmAuth ? '1' : '0' ?>"
            aria-pressed="<?= $bmEstado === 'visto' ? 'true' : 'false' ?>"
            aria-label="<?= e(t('auth.mark_seen')) ?>"
            title="<?= e(t('auth.mark_seen')) ?>">
        <span class="pc-poster-action__off"><?= icon('eye', 17) ?></span>
        <span class="pc-poster-action__on"><?= icon('check', 17) ?></span>
    </button>
</div>
