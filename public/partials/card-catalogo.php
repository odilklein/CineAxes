<?php

declare(strict_types=1);

/**
 * Card de um título do catálogo (filme ou série).
 *
 * Variável esperada: $obra, linha de catalogo_obras já com `generos` e `onde`.
 * O quick view usa os dados locais (sem rede): sinopse curta, gêneros, nota e
 * plataformas. O pôster é montado a partir do caminho da TMDB.
 */

$obra = $obra ?? [];
$obraTitulo = trim((string) ($obra['titulo'] ?? ''));

if ($obraTitulo === '') {
    return;
}

$obraSerie = (string) ($obra['tipo'] ?? 'filme') === 'serie';
$obraPoster = tmdb_img($obra['poster_path'] ?? null, 'w342');
$obraGeneros = array_values(array_filter(array_map(
    static fn (array $genero): string => (string) ($genero['nome'] ?? ''),
    (array) ($obra['generos'] ?? [])
)));
$obraOnde = array_values((array) ($obra['onde'] ?? ($obra['plataformas'] ?? [])));
$obraAno = isset($obra['ano']) && is_numeric($obra['ano']) ? (int) $obra['ano'] : null;
$obraNota = isset($obra['nota']) && is_numeric($obra['nota']) ? round((float) $obra['nota'], 1) : null;
$obraAfinidade = isset($obra['afinidade']) && is_numeric($obra['afinidade']) ? (float) $obra['afinidade'] : null;

$obraQuick = [
    'tipo'     => 'catalogo',
    'serie'    => $obraSerie,
    'titulo'   => $obraTitulo,
    'ano'      => $obraAno,
    'poster'   => $obraPoster !== '' ? $obraPoster : null,
    'sinopse'  => trim((string) ($obra['sinopse'] ?? '')) ?: null,
    'generos'  => $obraGeneros,
    'nota'     => $obraNota,
    'temporadas' => isset($obra['temporadas']) ? (int) $obra['temporadas'] : null,
    'onde'     => array_map(
        static fn (array $plataforma): array => [
            'nome'  => (string) ($plataforma['nome'] ?? ''),
            'logo'  => tmdb_logo($plataforma['logo'] ?? null),
            'preco' => isset($plataforma['preco']) && is_numeric($plataforma['preco'])
                ? (float) $plataforma['preco']
                : null,
        ],
        array_filter($obraOnde, 'is_array')
    ),
    'tmdb'     => isset($obra['tmdb_id']) ? (int) $obra['tmdb_id'] : null,
];
?>
<article class="pc-cat-card" data-quickview="<?= json_attr($obraQuick) ?>" tabindex="0" role="button"
         aria-label="Abrir ficha de <?= e($obraTitulo) ?>">
    <div class="pc-cat-card__poster">
        <?php if ($obraPoster !== ''): ?>
            <img src="<?= e($obraPoster) ?>" alt="Pôster de <?= e($obraTitulo) ?>" loading="lazy" decoding="async">
        <?php else: ?>
            <span class="pc-cat-card__fallback"><?= e($obraTitulo) ?></span>
        <?php endif; ?>

        <?php if ($obraAfinidade !== null): ?>
            <span class="pc-cat-card__match"><?= pct($obraAfinidade) ?></span>
        <?php endif; ?>
    </div>

    <div class="pc-cat-card__body">
        <span class="pc-cat-card__title"><?= e($obraTitulo) ?></span>
        <small class="pc-cat-card__meta">
            <?= $obraAno !== null ? (int) $obraAno . ' · ' : '' ?><?= $obraSerie ? 'Série' : 'Filme' ?><?= $obraNota !== null ? ' · ★ ' . numero($obraNota, 1) : '' ?>
        </small>

        <?php if ($obraOnde !== []): ?>
            <span class="pc-cat-card__where">
                <?php foreach (array_slice($obraOnde, 0, 4) as $plataforma): ?>
                    <?php $logo = tmdb_logo($plataforma['logo'] ?? null); ?>
                    <span class="pc-cat-card__provider" title="<?= e($plataforma['nome'] ?? '') ?>">
                        <?php if ($logo !== ''): ?>
                            <img src="<?= e($logo) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <?= e(mb_substr((string) ($plataforma['nome'] ?? '?'), 0, 1)) ?>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
                <?php if (count($obraOnde) > 4): ?>
                    <span class="pc-cat-card__more">+<?= count($obraOnde) - 4 ?></span>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
</article>
