<?php

declare(strict_types=1);

/**
 * Card vertical do perfil. Reaproveitado na análise completa e no capítulo final
 * da retrospectiva. Depende de $codigo, $tituloPerfil e $perfil.
 *
 * A partir desta versão, o card não mostra os 6 eixos: mostra as marcas que o
 * perfil gerou. A leitura técnica fica na análise completa.
 */

$storyChars = array_slice($perfil['filmes_top'] ?? $perfil['personagens'] ?? [], 0, 3);
$storyFilms = array_slice($perfil['estante'], 0, 3);
$storyMarks = array_slice($perfil['leituras'] ?? [], 0, 4);
$storyTotalMarcas = count($perfil['leituras'] ?? []);
$storyPlataforma = $perfil['plataformas'][0] ?? null;
$storyTrilha = $perfil['trilhas'][0] ?? null;
$storyGhost = !empty($storyGhost);
?>
<div class="pc-story<?= $storyGhost ? ' pc-story--ghost' : '' ?>"<?= $storyGhost
    ? ' aria-hidden="true"'
    : ' data-story data-codigo="' . e($codigo) . '"' ?>>
    <div class="pc-story__top">
        <span class="pc-story__brand">PersonaCut</span>
        <span class="pc-story__kicker"><?= e(t('resultado.story_kicker')) ?></span>
    </div>

    <div class="pc-story__marks">
        <span class="pc-story__marks-label">
            <?= e(t($storyTotalMarcas === 1 ? 'resultado.story_mark' : 'resultado.story_marks', ['n' => $storyTotalMarcas])) ?>
        </span>
        <ul>
            <?php foreach ($storyMarks as $marca): ?>
                <li style="--mark:<?= e($marca['cor']) ?>">
                    <i aria-hidden="true"></i>
                    <span><?= e($marca['curto']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <h2 class="pc-story__title"><?= e($tituloPerfil) ?></h2>
    <p class="pc-story__meta">
        <?php $storyTopo = $perfil['personagens'][0] ?? null; ?>
        <?php $storyRankKey = ($perfil['confianca']['rotulo'] ?? '') === 'baixa'
            ? 'resultado.story_rank_low'
            : 'resultado.story_rank'; ?>
        <?php if ($storyTopo !== null): ?>
            <span><?= e(t($storyRankKey, [
                'posicao' => (int) ($storyTopo['posicao'] ?? 1),
                'de' => (int) ($storyTopo['de'] ?? ($perfil['personagens_total'] ?? count($perfil['personagens'] ?? []))),
            ])) ?></span>
            <i aria-hidden="true">·</i>
        <?php endif; ?>
        <span><?= e($codigo) ?></span>
    </p>

    <?php if ($storyPlataforma !== null || $storyTrilha !== null): ?>
        <p class="pc-story__where">
            <?php if ($storyPlataforma !== null): ?>
                <span><?= e(t('resultado.story_platform', ['nome' => (string) $storyPlataforma['nome']])) ?></span>
            <?php endif; ?>
            <?php if ($storyTrilha !== null): ?>
                <span><?= e(t('resultado.story_trilha', ['nome' => (string) $storyTrilha['nome']])) ?></span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <div class="pc-story__actors">
        <?php foreach ($storyChars as $personagem): ?>
            <?php
            $foto = $personagem['ator_foto_url'] ?? '';
            $foto = $foto !== '' ? $foto : tmdb_img($personagem['poster_path'] ?? null, 'w185');
            $rotulo = (string) ($personagem['personagem'] ?? '') !== ''
                ? (string) $personagem['personagem']
                : (string) ($personagem['titulo'] ?? $personagem['nome'] ?? '');
            ?>
            <span class="pc-story__actor">
                <?php $fotoProxy = img_proxy($foto); ?>
                <span class="pc-story__avatar<?= $fotoProxy !== '' ? ' has-photo' : '' ?>"<?php if ($fotoProxy !== ''): ?> style="background-image:url('<?= e($fotoProxy) ?>')" role="img" aria-label="<?= e($rotulo) ?>"<?php endif; ?>>
                    <?php if ($fotoProxy === ''): ?>
                        <?= e(iniciais($rotulo)) ?>
                    <?php endif; ?>
                </span>
                <b><?= e(nome_curto($rotulo)) ?></b>
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
        <span class="pc-story__cta">
            <small><?= e(t('resultado.story_cta')) ?></small>
            <b>PersonaCut</b>
        </span>
        <span class="pc-story__code"><?= e($codigo) ?></span>
    </div>
</div>
