<?php

declare(strict_types=1);

/**
 * Card 9:16 do perfil. Reaproveitado na análise completa e no capítulo final
 * da retrospectiva. Depende de $codigo, $tituloPerfil e $perfil.
 */

$storyChars = array_slice($perfil['personagens'], 0, 3);
$storyFilms = array_slice($perfil['estante'], 0, 3);
?>
<div class="pc-story" data-story data-codigo="<?= e($codigo) ?>">
    <div class="pc-story__top">
        <span class="pc-story__brand">PersonaCut</span>
        <span class="pc-story__kicker"><?= e(t('resultado.story_kicker')) ?></span>
    </div>

    <div class="pc-story__rarity">
        <?php if ($raridade['confiavel']): ?>
            <strong><?= $raridade['um_em'] !== null ? e(t('resultado.rarity_one_in', ['n' => $raridade['um_em']])) : '—' ?></strong>
            <small><?= e(t('resultado.rarity_percent', ['pct' => numero($raridade['percentual'], 1)])) ?></small>
        <?php else: ?>
            <strong><?= e(t('resultado.rarity_initial')) ?></strong>
            <small><?= e(t('resultado.rarity_base', ['n' => $raridade['total']])) ?></small>
        <?php endif; ?>
    </div>

    <h2 class="pc-story__title"><?= e($tituloPerfil) ?></h2>
    <p class="pc-story__meta">
        <?= pct($perfil['afinidade_media']) ?> <?= e(t('resultado.affinity')) ?>
        · <?= e($codigo) ?>
    </p>

    <div class="pc-story__bars">
        <?php foreach ($perfil['linhas'] as $linha): ?>
            <div class="pc-story__bar">
                <span class="pc-story__bar-label"><?= e(eixo_nome($linha['eixo'])) ?></span>
                <span class="pc-story__bar-track">
                    <i style="width:<?= num_css($linha['pct_esq']) ?>%;background:<?= e($linha['cor_esq']) ?>"></i>
                    <i style="width:<?= num_css($linha['pct_dir']) ?>%;background:<?= e($linha['cor_dir']) ?>"></i>
                </span>
                <small><?= e($linha['polo'] === 'D' ? $linha['rotulo_dir'] : $linha['rotulo_esq']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="pc-story__actors">
        <?php foreach ($storyChars as $personagem): ?>
            <?php $foto = $personagem['ator_foto_url'] ?: $personagem['imagem_url']; ?>
            <span class="pc-story__actor">
                <?php $fotoProxy = img_proxy($foto); ?>
                <span class="pc-story__avatar<?= $fotoProxy !== '' ? ' has-photo' : '' ?>"<?php if ($fotoProxy !== ''): ?> style="background-image:url('<?= e($fotoProxy) ?>')" role="img" aria-label="<?= e($personagem['nome']) ?>"<?php endif; ?>>
                    <?php if ($fotoProxy === ''): ?>
                        <?= e(iniciais((string) $personagem['nome'])) ?>
                    <?php endif; ?>
                </span>
                <b><?= e(nome_curto((string) $personagem['nome'])) ?></b>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="pc-story__anchor">
        <span>
            <b><?= e(nome_curto((string) ($perfil['diretor']['nome'] ?? '—'))) ?></b>
            <small><?= e(t('resultado.types.diretor')) ?></small>
        </span>
        <span>
            <b><?= e(nome_curto((string) ($perfil['estudio']['nome'] ?? '—'))) ?></b>
            <small><?= e(t('resultado.types.estudio')) ?></small>
        </span>
    </div>

    <div class="pc-story__films">
        <?php foreach ($storyFilms as $filme): ?>
            <?php $posterProxy = img_proxy((string) ($filme['poster'] ?? '')); ?>
            <span class="pc-story__film<?= $posterProxy !== '' ? ' has-poster' : '' ?>"<?php if ($posterProxy !== ''): ?> style="background-image:url('<?= e($posterProxy) ?>')" role="img" aria-label="<?= e((string) $filme['titulo']) ?>"<?php endif; ?>>
                <?php if ($posterProxy === ''): ?>
                    <span><?= e(mb_substr((string) $filme['titulo'], 0, 22)) ?></span>
                <?php endif; ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="pc-story__foot">
        <span>personacut</span>
        <span class="pc-story__code"><?= e($codigo) ?></span>
    </div>
</div>
