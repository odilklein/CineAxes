<?php

declare(strict_types=1);

/**
 * "Elenco do seu repertório": atores que aparecem nos títulos de catálogo mais
 * próximos do perfil. O cálculo está em Services\ElencoService.
 */

$elenco = $perfil['elenco'] ?? [];

if ($elenco === []) {
    return;
}
?>
<section class="pc-section" id="elenco">
    <div class="pc-section__head">
        <div>
            <span class="pc-eyebrow"><?= e(t('elenco.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('elenco.title')) ?></h2>
        </div>
        <p class="pc-lead" style="font-size:14px"><?= e(t('elenco.subtitle')) ?></p>
    </div>

    <div class="pc-cast">
        <?php foreach ($elenco as $ator): ?>
            <?php
            $atorFoto = tmdb_img($ator['foto_path'] ?? null, 'w300');
            $atorObra = $ator['obras'][0] ?? null;
            $quickAtor = [
                'tipo'       => 'ator',
                'nome'       => (string) $ator['nome'],
                'ator'       => (string) $ator['nome'],
                'atorTmdb'   => (int) $ator['tmdb_id'],
                'atorFoto'   => $atorFoto !== '' ? $atorFoto : null,
                'personagem' => $atorObra['personagem'] ?? null,
                'obra'       => $atorObra['titulo'] ?? null,
                'ano'        => $atorObra['ano'] ?? null,
                'poster'     => $atorFoto !== '' ? $atorFoto : null,
            ];
            ?>
            <article class="pc-cast__card reveal" data-quickview="<?= json_attr($quickAtor) ?>"
                     tabindex="0" role="button" aria-label="Abrir ficha de <?= e($ator['nome']) ?>">
                <span class="pc-cast__photo<?= $atorFoto !== '' ? ' has-photo' : '' ?>"
                      <?php if ($atorFoto !== ''): ?>style="background-image:url('<?= e($atorFoto) ?>')" aria-hidden="true"<?php endif; ?>>
                    <?php if ($atorFoto === ''): ?>
                        <?= e(iniciais((string) $ator['nome'])) ?>
                    <?php endif; ?>
                </span>

                <b><?= e($ator['nome']) ?></b>
                <small><?= e(t(
                    (int) $ator['titulos'] === 1 ? 'elenco.titles_one' : 'elenco.titles',
                    ['n' => numero((int) $ator['titulos'], 0)]
                )) ?></small>

                <?php if ($atorObra !== null): ?>
                    <em>
                        <?= $atorObra['personagem'] ? e($atorObra['personagem']) . ' · ' : '' ?>
                        <?= e((string) $atorObra['titulo']) ?><?= !empty($atorObra['ano']) ? ' (' . (int) $atorObra['ano'] . ')' : '' ?>
                    </em>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="pc-trilhas__note"><?= e(t('elenco.note')) ?></p>
</section>
