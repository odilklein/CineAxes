<?php

declare(strict_types=1);

/**
 * "Onde assistir": pódio de plataformas para o perfil.
 *
 * Recebe $perfil já montado (PerfilController::montar). A nota de cada
 * plataforma é calculada em PlataformaRankingService; aqui só apresentação.
 * Nenhum dado de disponibilidade é buscado em tempo de request.
 */

$streamPlataformas = $perfil['plataformas'] ?? [];
$streamPorTrilha = $perfil['plataformas_trilha'] ?? [];
$streamNomes = $perfil['trilhas_nomes'] ?? [];
$streamTotal = (int) ($perfil['catalogo_total'] ?? 0);

if ($streamPlataformas === []) {
    return;
}

$streamPodio = array_slice($streamPlataformas, 0, 3);
$streamOutras = array_slice($streamPlataformas, 3);

uasort($streamPorTrilha, static function (array $a, array $b): int {
    return $b['nota'] <=> $a['nota']
        ?: $b['titulos'] <=> $a['titulos']
        ?: $b['afinidade_media'] <=> $a['afinidade_media'];
});

$streamPorTrilha = array_slice($streamPorTrilha, 0, 6, true);

$streamPreco = static function (?float $preco): string {
    if ($preco === null) {
        return t('streaming.price_unknown');
    }

    return t('streaming.price', ['preco' => numero($preco, 2)]);
};

$streamTrilhaNome = static function (string $slug) use ($streamNomes): string {
    return (string) ($streamNomes[$slug] ?? $slug);
};
?>
<section class="pc-section pc-stream-section" id="onde-assistir">
    <div class="pc-section__head">
        <div>
            <span class="pc-eyebrow"><?= e(t('streaming.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('streaming.title')) ?></h2>
        </div>
        <p class="pc-lead" style="font-size:14px">
            <?= e(t('streaming.subtitle', ['n' => numero($streamTotal, 0)])) ?>
        </p>
    </div>

    <div class="pc-stream">
        <?php foreach ($streamPodio as $indice => $plataforma): ?>
            <?php $logo = tmdb_logo($plataforma['logo_path'] ?? null); ?>
            <article class="pc-stream__card pc-stream__card--<?= $indice === 0 ? 'first' : 'next' ?> reveal">
                <header class="pc-stream__head">
                    <span class="pc-stream__rank"><?= $indice + 1 ?></span>

                    <span class="pc-stream__brand">
                        <?php if ($logo !== ''): ?>
                            <img src="<?= e($logo) ?>" alt="<?= e($plataforma['nome']) ?>" loading="lazy" decoding="async" width="36" height="36">
                        <?php endif; ?>
                        <span>
                            <b><?= e($plataforma['nome']) ?></b>
                            <small><?= e($streamPreco($plataforma['preco_mensal'])) ?></small>
                            <?php if (!empty($plataforma['url'])): ?>
                                <a class="pc-stream__link" href="<?= e((string) $plataforma['url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= e(t('streaming.subscribe')) ?> <?= icon('external', 11) ?>
                                </a>
                            <?php endif; ?>
                        </span>
                    </span>

                    <span class="pc-stream__score">
                        <b><?= pct((float) $plataforma['nota']) ?></b>
                        <small><?= e(t('streaming.score')) ?></small>
                    </span>
                </header>

                <p class="pc-stream__summary">
                    <b><?= e(t('streaming.titles', ['n' => numero((int) $plataforma['titulos'], 0)])) ?></b>
                    · <?= e(t('streaming.exclusive', ['n' => numero((int) $plataforma['exclusivos'], 0)])) ?>
                    · <?= e(t('streaming.avg', ['pct' => pct((float) $plataforma['afinidade_media'])])) ?>
                </p>

                <?php if (!empty($plataforma['melhores'])): ?>
                    <div class="pc-stream__films">
                        <?php foreach (array_slice($plataforma['melhores'], 0, 3) as $obra): ?>
                            <?php
                            $poster = tmdb_img($obra['poster_path'] ?? null, 'w185');
                            $quick = [
                                'tipo'    => 'catalogo',
                                'serie'   => (string) ($obra['tipo'] ?? 'filme') === 'serie',
                                'titulo'  => (string) ($obra['titulo'] ?? ''),
                                'ano'     => $obra['ano'] ?? null,
                                'poster'  => $poster !== '' ? $poster : null,
                                'sinopse' => $obra['sinopse'] ?? null,
                                'generos' => array_map(
                                    static fn (array $g): string => (string) ($g['nome'] ?? ''),
                                    (array) ($obra['generos'] ?? [])
                                ),
                                'nota'    => $obra['nota'] ?? null,
                                'temporadas' => $obra['temporadas'] ?? null,
                                'onde'    => array_map(
                                    static fn (array $p): array => [
                                        'nome'  => (string) ($p['nome'] ?? ''),
                                        'logo'  => tmdb_logo($p['logo'] ?? null),
                                        'preco' => $p['preco'] ?? null,
                                    ],
                                    (array) ($obra['plataformas'] ?? [])
                                ),
                                'tmdb'    => $obra['tmdb_id'] ?? null,
                            ];
                            ?>
                            <span class="pc-stream__film<?= $poster !== '' ? ' has-poster' : '' ?>"
                                  data-quickview="<?= json_attr($quick) ?>" tabindex="0" role="button"
                                  aria-label="Abrir ficha de <?= e((string) $obra['titulo']) ?>"
                                  <?php if ($poster !== ''): ?>style="background-image:url('<?= e($poster) ?>')"<?php endif; ?>>
                                <?php if ($poster === ''): ?>
                                    <i><?= e(mb_substr((string) $obra['titulo'], 0, 18)) ?></i>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($plataforma['trilhas'])): ?>
                    <div class="pc-stream__tags">
                        <?php foreach (array_slice(array_keys($plataforma['trilhas']), 0, 3) as $slug): ?>
                            <span class="pc-stream__tag">
                                <?= e($streamTrilhaNome((string) $slug)) ?>
                                <b><?= (int) $plataforma['trilhas'][$slug] ?></b>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($plataforma['exemplos_exclusivos'])): ?>
                    <p class="pc-stream__exclusive">
                        <span><?= e(t('streaming.exclusive_label')) ?></span>
                        <?= e(implode(' · ', array_map(
                            static fn (array $obra): string => (string) $obra['titulo'],
                            $plataforma['exemplos_exclusivos']
                        ))) ?>
                    </p>
                <?php endif; ?>

                <dl class="pc-stream__parts">
                    <?php foreach (['relevancia', 'cobertura', 'exclusividade', 'diversidade', 'preco'] as $parte): ?>
                        <div>
                            <dt><?= e(t('streaming.parts.' . $parte)) ?></dt>
                            <dd><span style="width:<?= num_css((float) ($plataforma['componentes'][$parte] ?? 0)) ?>%"></span></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($streamOutras !== []): ?>
        <div class="pc-stream__others">
            <?php foreach ($streamOutras as $plataforma): ?>
                <?php $logo = tmdb_logo($plataforma['logo_path'] ?? null); ?>
                <div class="pc-stream__row">
                    <span class="pc-stream__row-brand">
                        <?php if ($logo !== ''): ?>
                            <img src="<?= e($logo) ?>" alt="" loading="lazy" decoding="async" width="24" height="24">
                        <?php endif; ?>
                        <b><?= e($plataforma['nome']) ?></b>
                    </span>
                    <span><?= e(t('streaming.titles', ['n' => numero((int) $plataforma['titulos'], 0)])) ?></span>
                    <span><?= e($streamPreco($plataforma['preco_mensal'])) ?></span>
                    <span class="pc-stream__row-score"><?= pct((float) $plataforma['nota']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($streamPorTrilha !== []): ?>
        <div class="pc-best">
            <div class="pc-best__head">
                <h3><?= e(t('streaming.best_title')) ?></h3>
                <p><?= e(t('streaming.best_sub')) ?></p>
            </div>

            <div class="pc-best__grid">
                <?php foreach ($streamPorTrilha as $slug => $melhor): ?>
                    <?php
                    $melhorLogo = tmdb_logo($melhor['logo_path'] ?? null);
                    $melhorNome = (string) ($melhor['nome'] ?? $streamTrilhaNome((string) $slug));
                    $melhorAfinidade = (float) ($melhor['afinidade_media'] ?? 0);
                    ?>
                    <article class="pc-best__card" style="--trilha:<?= e($melhor['cor'] ?? '#2f4858') ?>">
                        <header class="pc-best__top">
                            <span class="pc-best__trilha">
                                <?= icon((string) ($melhor['icone'] ?? 'film'), 14) ?>
                                <?= e($melhorNome) ?>
                            </span>

                            <span class="pc-best__platform">
                                <?php if ($melhorLogo !== ''): ?>
                                    <img src="<?= e($melhorLogo) ?>" alt="" loading="lazy" decoding="async" width="18" height="18">
                                <?php endif; ?>
                                <?= e($melhor['plataforma']) ?>
                            </span>
                        </header>

                        <div class="pc-best__stat">
                            <b><?= numero((int) $melhor['titulos'], 0) ?></b>
                            <span><?= e(t('streaming.best_titles')) ?></span>
                        </div>

                        <div class="pc-best__bar">
                            <span style="width:<?= num_css(min(100.0, $melhorAfinidade)) ?>%"></span>
                        </div>

                        <div class="pc-best__meta">
                            <span><?= pct($melhorAfinidade) ?> <?= e(t('streaming.affinity_short')) ?></span>
                            <span><?= pct((float) ($melhor['especializacao'] ?? 0)) ?> <?= e(t('streaming.acervo_short')) ?></span>
                        </div>

                        <?php if (!empty($melhor['melhores'])): ?>
                            <div class="pc-best__films">
                                <?php foreach ($melhor['melhores'] as $exemplo): ?>
                                    <?php $exemploPoster = tmdb_img($exemplo['poster_path'] ?? null, 'w185'); ?>
                                    <span class="pc-best__film<?= $exemploPoster !== '' ? ' has-poster' : '' ?>"
                                          <?php if ($exemploPoster !== ''): ?>style="background-image:url('<?= e($exemploPoster) ?>')" aria-hidden="true"<?php endif; ?>>
                                        <?php if ($exemploPoster === ''): ?>
                                            <i><?= e(mb_substr((string) $exemplo['titulo'], 0, 10)) ?></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>

                                <p class="pc-best__names">
                                    <?= e(implode(' · ', array_map(
                                        static fn (array $exemplo): string => (string) $exemplo['titulo'],
                                        $melhor['melhores']
                                    ))) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $streamDataPreco = null;

    foreach ($streamPlataformas as $plataforma) {
        if (!empty($plataforma['preco_atualizado_em'])) {
            $streamDataPreco = (string) $plataforma['preco_atualizado_em'];
            break;
        }
    }
    ?>
    <p class="pc-stream__note">
        <?= e(t('streaming.note')) ?>
        <?php if ($streamDataPreco !== null): ?>
            <?= e(t('streaming.price_checked', [
                'data' => date('d/m/Y', (int) strtotime($streamDataPreco)),
            ])) ?>
        <?php endif; ?>
    </p>
</section>
