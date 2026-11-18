<?php

declare(strict_types=1);

/**
 * Card de filme (pôster + ficha rápida).
 *
 * Variáveis esperadas:
 * - $filme: ['titulo' => string, 'ano' => int|null, 'tmdb' => int|null,
 *            'poster' => string|null, 'origem' => ?string, 'personagem' => ?string]
 * - $filmeVariante: 'chip' (padrão) | 'card' | 'mini'
 */

use Models\FilmeCard;

$filme = $filme ?? [];
$filmeTitulo = trim((string) ($filme['titulo'] ?? ''));

if ($filmeTitulo === '') {
    return;
}

$filme = FilmeCard::enriquecer([$filme])[0];
$filmeVariante = $filmeVariante ?? 'chip';
$filmeOrigem = trim((string) ($filme['origem'] ?? ''));
$filmePersonagem = trim((string) ($filme['personagem'] ?? ''));

$filmeQuick = [
    'tipo'       => 'filme',
    'tmdb'       => $filme['tmdb'],
    'titulo'     => $filmeTitulo,
    'ano'        => $filme['ano'],
    'poster'     => $filme['poster'],
    'origem'      => $filmeOrigem !== '' ? $filmeOrigem : null,
    'personagem' => $filmePersonagem !== '' ? $filmePersonagem : null,
];
?>
<button class="pc-filme pc-filme--<?= e($filmeVariante) ?>" type="button"
        data-quickview="<?= json_attr($filmeQuick) ?>"
        aria-label="Abrir ficha de <?= e($filmeTitulo) ?>">
    <span class="pc-filme__poster">
        <?php if (!empty($filme['poster'])): ?>
            <img src="<?= e(img_proxy((string) $filme['poster'])) ?>" alt="" loading="lazy" decoding="async">
        <?php else: ?>
            <span class="pc-filme__mark" aria-hidden="true"><?= e(iniciais($filmeTitulo)) ?></span>
        <?php endif; ?>
    </span>
    <span class="pc-filme__body">
        <b class="pc-filme__title"><?= e($filmeTitulo) ?></b>
        <small class="pc-filme__meta">
            <?= $filme['ano'] !== null ? (int) $filme['ano'] : '' ?>
            <?php if ($filmePersonagem !== ''): ?>
                <span class="pc-filme__role"><?= e($filmePersonagem) ?></span>
            <?php endif; ?>
        </small>
    </span>
</button>
