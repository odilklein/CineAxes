<?php

declare(strict_types=1);

/**
 * Cards de curadoria do perfil ("Doramas para você", "Filmes indianos"...).
 *
 * As trilhas vêm de config/recomendacao.php e os títulos são do catálogo,
 * ordenados por afinidade e limitados a 6. Card sem títulos suficientes não
 * é renderizado (regra em TrilhaService).
 */

$trilhasCards = $perfil['trilhas'] ?? [];

if ($trilhasCards === []) {
    return;
}
?>
<section class="pc-section pc-trilhas" id="trilhas">
    <div class="pc-section__head">
        <div>
            <span class="pc-eyebrow"><?= e(t('trilhas.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('trilhas.title')) ?></h2>
        </div>
        <p class="pc-lead" style="font-size:14px"><?= e(t('trilhas.subtitle')) ?></p>
    </div>

    <div class="pc-trilhas__grid">
        <?php foreach ($trilhasCards as $trilha): ?>
            <article class="pc-trilha reveal" style="--trilha:<?= e($trilha['cor']) ?>">
                <header class="pc-trilha__head">
                    <span class="pc-trilha__icon"><?= icon((string) $trilha['icone'], 18) ?></span>
                    <div>
                        <h3><?= e($trilha['nome']) ?></h3>
                        <p><?= e($trilha['descricao']) ?></p>
                    </div>
                    <span class="pc-trilha__count">
                        <?= numero((int) $trilha['total'], 0) ?>
                        <small><?= e(t('trilhas.fit', ['pct' => pct((float) ($trilha['encaixe'] ?? 50))])) ?></small>
                    </span>
                </header>

                <div class="pc-trilha__slider" data-slider>
                    <button class="pc-trilha__nav pc-trilha__nav--prev" type="button"
                            data-slider-prev aria-label="<?= e(t('trilhas.prev')) ?>">
                        <?= icon('arrow-left', 15) ?>
                    </button>

                    <div class="pc-trilha__films" data-slider-track>
                        <?php foreach ($trilha['titulos'] as $obra): ?>
                            <?php require __DIR__ . '/../card-catalogo.php'; ?>
                        <?php endforeach; ?>
                    </div>

                    <button class="pc-trilha__nav pc-trilha__nav--next" type="button"
                            data-slider-next aria-label="<?= e(t('trilhas.next')) ?>">
                        <?= icon('arrow-right', 15) ?>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="pc-trilhas__note">
        <?= e(t('trilhas.note', ['n' => numero((int) ($perfil['catalogo_total'] ?? 0), 0)])) ?>
    </p>
</section>
