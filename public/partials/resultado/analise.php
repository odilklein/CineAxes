<?php

declare(strict_types=1);

/**
 * Análise completa do perfil (dashboard). Recebe o view model montado em
 * resultado.php: $perfil, $codigo, $tituloPerfil, $arquetipo,
 * $radar, $temGroq e $raridade.
 */

$temRetro = $temRetro ?? false;
?>

    <section class="pc-result">
        <div class="pc-wrap">
            <div class="pc-result__head">
                <div>
                    <span class="pc-eyebrow"><?= e(t('resultado.eyebrow')) ?></span>
                    <h1 class="pc-title"><?= e(t('resultado.title')) ?></h1>
                </div>

                <div class="pc-result__code">
                    <?php if (!empty($temRetro)): ?>
                        <a class="pc-btn pc-btn--sm pc-btn--ghost pc-retro-open" href="<?= e(url('resultado.php', ['codigo' => $codigo])) ?>">
                            <?= icon('play', 14) ?>
                            <?= e(t('retro.open')) ?>
                        </a>
                    <?php endif; ?>
                    <span><?= e(t('resultado.meta', [
                        'codigo' => $codigo,
                        'modo'   => modo_rotulo((string) $perfil['resultado']['modo']),
                    ])) ?></span>
                    <button class="pc-code" type="button" data-copy="<?= e($codigo) ?>" data-copy-ok="<?= e(t('ui.copied')) ?>">
                        <?= e($codigo) ?>
                        <?= icon('copy', 14) ?>
                    </button>
                </div>
            </div>

            <article class="pc-profile-card reveal">
                <div class="pc-profile-card__main">
                    <span class="pc-profile-card__badge">
                        <?= icon('compass', 14) ?>
                        <?= e(t('resultado.archetype_label')) ?>
                    </span>

                    <h2 class="pc-profile-card__title"><?= e($tituloPerfil) ?></h2>

                    <?php if ($perfil['leituras'] !== []): ?>
                        <div class="pc-profile-card__poles">
                            <?php foreach (array_slice($perfil['leituras'], 0, 2) as $leitura): ?>
                                <span class="pc-chip">
                                    <i class="pc-chip__dot" style="background: <?= e($leitura['cor']) ?>"></i>
                                    <?= e($leitura['curto']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <p class="pc-profile-card__desc">
                        <?= e($arquetipo['descricao'] ?? t('resultado.neutral_desc')) ?>
                    </p>

                    <div class="pc-ritual">
                        <span class="pc-ritual__icon"><?= icon('film', 19) ?></span>
                        <div>
                            <strong><?= e(t('resultado.ritual_label')) ?></strong>
                            <p><?= e($arquetipo['ritual_cinema'] ?? t('resultado.neutral_ritual')) ?></p>
                        </div>
                    </div>

                    <div class="pc-ficha">
                        <div class="pc-ficha__item">
                            <span><?= e(t('resultado.ficha_affinity')) ?></span>
                            <b><?= pct($perfil['afinidade_media']) ?></b>
                        </div>
                        <div class="pc-ficha__item pc-ficha__item--reading">
                            <span><?= e(t('resultado.ficha_reading')) ?></span>
                            <b><?= e($perfil['leituras'][0]['curto'] ?? '—') ?></b>
                        </div>
                        <div class="pc-ficha__item">
                            <span><?= e(t('resultado.ficha_decade')) ?></span>
                            <b><?= e($perfil['insights']['decada']['label'] ?? '—') ?></b>
                        </div>
                    </div>
                </div>

                <aside class="pc-profile-card__side pc-story-side" data-story-side>
                    <?php require __DIR__ . '/story-card.php'; ?>

                    <button class="pc-btn pc-btn--sm pc-btn--light pc-story-share" type="button"
                            data-story-share
                            data-codigo="<?= e($codigo) ?>"
                            data-msg-preparing="<?= e(t('resultado.story_preparing')) ?>"
                            data-msg-error="<?= e(t('resultado.story_error')) ?>"
                            data-msg-download="<?= e(t('resultado.story_download')) ?>">
                        <?= icon('share', 15) ?>
                        <span><?= e(t('resultado.story_share')) ?></span>
                    </button>
                    <p class="pc-story__note" data-story-note><?= e(t('resultado.story_note')) ?></p>
                </aside>
            </article>


            <?php if ($temGroq): ?>
                <section class="pc-panel pc-critico reveal" data-retrato<?= !$modoCompleto ? ' data-retrato-lazy' : '' ?> data-codigo="<?= e($codigo) ?>" data-erro="<?= e(t('resultado.critic_error')) ?>">
                    <div class="pc-critico__head">
                        <span class="pc-eyebrow"><?= e(t('resultado.critic_eyebrow')) ?></span>
                        <h2><?= e(t('resultado.critic_title')) ?></h2>
                        <p><?= e(t('resultado.critic_subtitle')) ?></p>
                    </div>

                    <div class="pc-critico__texto" data-retrato-texto>
                        <?php if (!empty($perfil['retrato_salvo']['texto'])): ?>
                            <?php foreach (preg_split('/\n{2,}/', (string) $perfil['retrato_salvo']['texto']) as $paragrafo): ?>
                                <p><?= e($paragrafo) ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="pc-critico__loading"><?= e(t('resultado.critic_loading')) ?></p>
                        <?php endif; ?>
                    </div>

                    <p class="pc-critico__nota"><?= e(t('resultado.critic_note')) ?></p>
                </section>
            <?php endif; ?>

            <section class="pc-panel pc-readings reveal" id="leituras">
                <div class="pc-panel__head">
                    <div>
                        <h2><?= e(t('resultado.readings_title')) ?></h2>
                        <p><?= e(t('resultado.readings_subtitle')) ?></p>
                    </div>
                </div>

                <div class="pc-readings__grid<?= count($perfil['leituras']) === 1 ? ' pc-readings__grid--single' : '' ?>">
                    <?php foreach ($perfil['leituras'] as $indice => $leitura): ?>
                        <article class="pc-reading" style="--reading:<?= e($leitura['cor']) ?>">
                            <div class="pc-reading__top">
                                <span class="pc-reading__num"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="pc-reading__eixo"><?= e($leitura['eixo']) ?> · <?= e($leitura['polo']) ?></span>
                                <span class="pc-reading__forca"><?= e($leitura['forca']) ?></span>
                            </div>

                            <h3 class="pc-reading__title"><?= e($leitura['manchete']) ?></h3>
                            <p class="pc-reading__body"><?= e($leitura['corpo']) ?></p>

                            <div class="pc-reading__films">
                                <span><?= e(t('retro.reading.matches')) ?></span>
                                <ul>
                                    <?php foreach ($leitura['combina'] as $filme): ?>
                                        <li><?= e($filme['titulo']) ?> <small><?= (int) $filme['ano'] ?></small></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <p class="pc-reading__tired">
                                <span><?= e(t('retro.reading.tired')) ?></span>
                                <?= e($leitura['cansa']) ?>
                            </p>

                            <?php if ($leitura['espelho'] !== null): ?>
                                <p class="pc-reading__mirror">
                                    <?= e(t('retro.reading.mirror', [
                                        'nome' => $leitura['espelho']['nome'],
                                        'obra' => $leitura['espelho']['obra'],
                                    ])) ?>
                                </p>
                            <?php endif; ?>

                            <span class="pc-reading__technical">
                                <?= e($leitura['pct'] !== null
                                    ? t('retro.reading.technical', [
                                        'eixo' => $leitura['eixo'],
                                        'polo' => $leitura['polo'],
                                        'pct' => numero((float) $leitura['pct'], 0),
                                    ])
                                    : t('retro.reading.technical_neutral', [
                                        'eixo' => $leitura['eixo'],
                                        'polo' => $leitura['polo'],
                                    ])) ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="pc-readings__note"><?= e(t('resultado.readings_note')) ?></p>
            </section>

            <details class="pc-dados">
                <summary>
                    <span><?= e(t('resultado.technical_title')) ?></span>
                    <small><?= e(t('resultado.technical_help')) ?></small>
                </summary>

                <div class="pc-dados__body">
                    <div class="pc-result__grid">
                        <section class="pc-panel">
                            <div class="pc-panel__head">
                                <div>
                                    <h2><?= e(t('resultado.axes_title')) ?></h2>
                                    <p><?= e(t('resultado.axes_subtitle')) ?></p>
                                </div>
                            </div>

                            <?php foreach ($perfil['linhas'] as $linha): ?>
                                <div class="pc-axis-line">
                                    <div class="pc-axis-line__top">
                                        <span class="pc-axis-line__name">
                                            <?= icon((string) $linha['eixo']['icone_esq'], 14) ?>
                                            <?= e(eixo_nome($linha['eixo'])) ?>
                                        </span>
                                        <span class="pc-axis-line__intensity" data-level="<?= e($linha['intensidade']) ?>">
                                            <?= e(t('resultado.intensity.' . $linha['intensidade'])) ?>
                                        </span>
                                    </div>

                                    <div class="pc-dual">
                                        <span class="pc-dual__pole"><b><?= pct($linha['pct_esq']) ?></b> <?= e($linha['rotulo_esq']) ?></span>
                                        <span class="pc-dual__track">
                                            <span class="pc-dual__esq" data-bar="<?= num_css($linha['pct_esq']) ?>" style="width:0;background:<?= e($linha['cor_esq']) ?>"></span>
                                            <span class="pc-dual__dir" data-bar="<?= num_css($linha['pct_dir']) ?>" style="width:0;background:<?= e($linha['cor_dir']) ?>"></span>
                                        </span>
                                        <span class="pc-dual__pole pc-dual__pole--dir"><?= e($linha['rotulo_dir']) ?> <b><?= pct($linha['pct_dir']) ?></b></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>

                        <section class="pc-panel pc-panel--radar reveal">
                            <div class="pc-panel__head">
                                <div>
                                    <h2><?= e(t('resultado.radar_title')) ?></h2>
                                    <p><?= e(t('resultado.radar_subtitle')) ?></p>
                                </div>
                            </div>

                            <div class="pc-radar-wrap">
                                <?= $radar ?>
                            </div>

                            <p class="pc-radar__caption"><?= e(t('resultado.radar_caption')) ?></p>
                        </section>
                    </div>
                </div>
            </details>

            <?php $ins = $perfil['insights']; ?>
            <section class="pc-section" id="insights">
                <div class="pc-section__head">
                    <div>
                        <span class="pc-eyebrow"><?= e(t('resultado.insights.eyebrow')) ?></span>
                        <h2 class="pc-title"><?= e(t('resultado.insights.title')) ?></h2>
                    </div>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.insights.subtitle')) ?></p>
                </div>

                <div class="pc-insights">
                    <?php if ($ins['decada'] !== null): ?>
                        <article class="pc-insight pc-insight--decade reveal">
                            <span class="pc-insight__icon"><?= icon('clock', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.decada_title')) ?></span>
                            <span class="pc-insight__value pc-insight__value--big"><?= e($ins['decada']['label']) ?></span>
                            <p><?= e(t('resultado.insights.decada_text', [
                                'descricao' => $ins['decada']['descricao'],
                                'amostra' => $ins['decada']['amostra'],
                            ])) ?></p>
                        </article>
                    <?php endif; ?>

                    <?php if ($ins['oposto'] !== null): ?>
                        <article class="pc-insight reveal">
                            <span class="pc-insight__icon"><?= icon('x', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.oposto_title')) ?></span>
                            <span class="pc-insight__value"><?= e($ins['oposto']['nome']) ?></span>
                            <p><?= e(t('resultado.insights.oposto_text', [
                                'tipo' => t('resultado.types.' . $ins['oposto']['tipo']),
                                'obra' => $ins['oposto']['obra'],
                                'pct' => numero($ins['oposto']['afinidade'], 0),
                            ])) ?></p>
                            <p><?= e(t('retro.insights.oposto_cta', ['obra' => $ins['oposto']['obra']])) ?></p>
                        </article>
                    <?php endif; ?>

                    <?php if ($ins['secundario'] !== null): ?>
                        <article class="pc-insight reveal">
                            <span class="pc-insight__icon"><?= icon('layers', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.secundario_title')) ?></span>
                            <span class="pc-insight__value"><?= e($ins['secundario']['titulo']) ?></span>
                            <p><?= e(t('resultado.insights.secundario_text', [
                                'pct' => numero($ins['secundario']['compatibilidade'], 0),
                            ])) ?></p>
                        </article>
                    <?php endif; ?>
                </div>

                <?php if ($ins['top10'] !== []): ?>
                    <section class="pc-panel pc-top10-panel reveal">
                        <div class="pc-panel__head">
                            <div>
                                <h2><?= e(t('resultado.insights.top10_title')) ?></h2>
                                <p>
                                    <?= e(t('resultado.insights.top10_sub')) ?>
                                    · <?= e(t('resultado.insights.base', ['n' => $ins['base_total']])) ?>
                                </p>
                            </div>
                        </div>

                        <div class="pc-top10">
                            <?php foreach ($ins['top10'] as $indice => $item): ?>
                                <div class="pc-top-row">
                                    <span class="pc-top-row__rank"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <span class="pc-top-row__avatar">
                                        <?php if (!empty($item['imagem'])): ?>
                                            <img src="<?= e($item['imagem']) ?>" alt="" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <?= e(iniciais((string) $item['nome'])) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="pc-top-row__info">
                                        <b><?= e($item['nome']) ?></b>
                                        <small><?= e(t('resultado.types.' . $item['tipo'])) ?> · <?= e($item['obra']) ?></small>
                                    </span>
                                    <span class="pc-top-row__score"><?= pct($item['afinidade']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </section>

            <section class="pc-section" id="espelhos">
                <div class="pc-section__head">
                    <div>
                        <span class="pc-eyebrow"><?= e(t('resultado.characters_title')) ?></span>
                        <h2 class="pc-title"><?= e(t('resultado.characters_subtitle')) ?></h2>
                    </div>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.characters_hint')) ?></p>
                </div>

                <?php if ($perfil['personagens'] === []): ?>
                    <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
                <?php else: ?>
                    <div class="pc-entity-grid pc-entity-grid--rich">
                        <?php foreach ($perfil['personagens'] as $indice => $entidade): ?>
                            <?php
                            $cor = cor_dominante($entidade['vetor'], $perfil['eixos']);
                            $quick = [
                                'tipo' => 'personagem',
                                'tmdb' => $entidade['tmdb_id'],
                                'nome' => $entidade['nome'],
                                'titulo' => $entidade['obra_origem'],
                                'ano' => $entidade['ano'],
                                'poster' => $entidade['imagem_url'],
                                'bio' => $entidade['bio'],
                                'ator' => $entidade['ator_nome'],
                                'atorFoto' => $entidade['ator_foto_url'],
                                'afinidade' => $entidade['afinidade'],
                            ];
                            ?>
                            <article class="pc-entity pc-entity--rich reveal"
                                     data-quickview="<?= json_attr($quick) ?>"
                                     tabindex="0" role="button"
                                     aria-label="Abrir ficha de <?= e($entidade['nome']) ?>">
                                <div class="pc-entity__poster">
                                    <?php if (!empty($entidade['imagem_url'])): ?>
                                        <img src="<?= e($entidade['imagem_url']) ?>" alt="Pôster de <?= e($entidade['obra_origem']) ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span class="pc-entity__mono-lg" style="--mono-bg: <?= e($cor) ?>"><?= e(iniciais((string) $entidade['nome'])) ?></span>
                                    <?php endif; ?>
                                    <span class="pc-entity__rank"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <span class="pc-quick-hint">Ficha rápida</span>
                                </div>

                                <div class="pc-entity__content">
                                    <div class="pc-entity__name"><?= e($entidade['nome']) ?></div>
                                    <div class="pc-entity__meta">
                                        <?= e($entidade['obra_origem']) ?><?= $entidade['ano'] !== null ? ' · ' . (int) $entidade['ano'] : '' ?>
                                    </div>

                                    <p class="pc-entity__bio"><?= e((string) ($entidade['bio'] ?? '')) ?></p>

                                    <?php if (!empty($entidade['ator_nome'])): ?>
                                        <div class="pc-entity__actor">
                                            <span class="pc-entity__actor-photo">
                                                <?php if (!empty($entidade['ator_foto_url'])): ?>
                                                    <img src="<?= e($entidade['ator_foto_url']) ?>" alt="" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                    <?= e(iniciais((string) $entidade['ator_nome'])) ?>
                                                <?php endif; ?>
                                            </span>
                                            <span>
                                                <b><?= e($entidade['ator_nome']) ?></b>
                                                <small>
                                                    <?= e(origem_curta($entidade['pais'] ?? null)) ?><?= $entidade['nascimento'] !== null ? ' · ' . (int) $entidade['nascimento'] : '' ?>
                                                </small>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="pc-entity__foot">
                                        <span class="pc-entity__type"><?= e(t('resultado.types.personagem')) ?></span>
                                        <span class="pc-affinity">
                                            <b><?= pct($entidade['afinidade']) ?></b>
                                            <small><?= e(t('resultado.affinity')) ?></small>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pc-two-col pc-mt-3">
                    <?php foreach ([
                        ['item' => $perfil['diretor'], 'tipo' => 'diretor', 'icone' => 'film'],
                        ['item' => $perfil['estudio'], 'tipo' => 'estudio', 'icone' => 'star'],
                    ] as $bloco): ?>
                        <?php if ($bloco['item'] === null): ?>
                            <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
                            <?php continue; ?>
                        <?php endif; ?>

                        <?php
                        $item = $bloco['item'];
                        $cor = cor_dominante($item['vetor'], $perfil['eixos']);
                        $ehEstudio = $bloco['tipo'] === 'estudio';
                        $quick = [
                            'tipo' => $bloco['tipo'],
                            'nome' => $item['nome'],
                            'bio' => $item['bio'],
                            'poster' => $item['imagem_url'],
                            'obras' => array_map(
                                static fn (array $obra): array => ['titulo' => $obra['titulo'], 'ano' => $obra['ano']],
                                $item['obras'] ?? []
                            ),
                            'afinidade' => $item['afinidade'],
                        ];
                        ?>
                        <article class="pc-entity pc-entity--rich pc-entity--anchor reveal"
                                 data-quickview="<?= json_attr($quick) ?>"
                                 tabindex="0" role="button"
                                 aria-label="Abrir ficha de <?= e($item['nome']) ?>">
                            <div class="pc-entity__poster <?= $ehEstudio ? 'pc-entity__poster--logo' : '' ?>">
                                <?php if (!empty($item['imagem_url'])): ?>
                                    <img src="<?= e($item['imagem_url']) ?>" alt="" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <span class="pc-entity__mono-lg" style="--mono-bg: <?= e($cor) ?>"><?= e(iniciais((string) $item['nome'])) ?></span>
                                <?php endif; ?>
                                <span class="pc-quick-hint">Ficha rápida</span>
                            </div>

                            <div class="pc-entity__content">
                                <div class="pc-entity__name"><?= e($item['nome']) ?></div>
                                <div class="pc-entity__meta">
                                    <?= e(origem_curta($item['pais'] ?? null)) ?>
                                    <?= $item['nascimento'] !== null ? ' · ' . (int) $item['nascimento'] : '' ?>
                                    <?= $item['pais'] === null && $item['nascimento'] === null ? e($item['obra_origem']) : '' ?>
                                </div>

                                <p class="pc-entity__bio"><?= e((string) ($item['bio'] ?? '')) ?></p>

                                <?php if (!empty($item['obras'])): ?>
                                    <ul class="pc-works">
                                        <?php foreach (array_slice($item['obras'], 0, 3) as $obra): ?>
                                            <li>
                                                <span><?= e($obra['titulo']) ?></span>
                                                <small><?= $obra['ano'] !== null ? (int) $obra['ano'] : '' ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <div class="pc-entity__foot">
                                    <span class="pc-entity__type"><?= e(t('resultado.types.' . $bloco['tipo'])) ?></span>
                                    <span class="pc-affinity">
                                        <b><?= pct($item['afinidade']) ?></b>
                                        <small><?= e(t('resultado.affinity')) ?></small>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($perfil['estante'] !== []): ?>
                <section class="pc-section" id="estante">
                    <div class="pc-section__head">
                        <div>
                            <span class="pc-eyebrow"><?= e(t('resultado.shelf_eyebrow')) ?></span>
                            <h2 class="pc-title"><?= e(t('resultado.shelf_title')) ?></h2>
                        </div>
                        <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.shelf_subtitle')) ?></p>
                    </div>

                    <div class="pc-shelf">
                        <?php foreach ($perfil['estante'] as $obra): ?>
                            <?php
                            $quick = [
                                'tipo' => 'filme',
                                'tmdb' => $obra['tmdb'] ?? null,
                                'titulo' => $obra['titulo'],
                                'ano' => $obra['ano'],
                                'poster' => $obra['poster'],
                                'origem' => $obra['origem'],
                                'personagem' => $obra['personagem'],
                            ];
                            ?>
                            <article class="pc-shelf__item"
                                     data-quickview="<?= json_attr($quick) ?>"
                                     tabindex="0" role="button"
                                     aria-label="Abrir ficha de <?= e($obra['titulo']) ?>">
                                <div class="pc-shelf__poster">
                                    <?php if (!empty($obra['poster'])): ?>
                                        <img src="<?= e($obra['poster']) ?>" alt="Pôster de <?= e($obra['titulo']) ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span class="pc-shelf__fallback"><?= e($obra['titulo']) ?></span>
                                    <?php endif; ?>
                                    <span class="pc-quick-hint">Ficha</span>
                                </div>
                                <span class="pc-shelf__title"><?= e($obra['titulo']) ?></span>
                                <small><?= $obra['ano'] !== null ? (int) $obra['ano'] . ' · ' : '' ?><?= e($obra['origem']) ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="pc-share">
                <section class="pc-share__box">
                    <span class="pc-eyebrow"><?= e(t('resultado.share_title')) ?></span>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.share_subtitle')) ?></p>

                    <div class="pc-share__code-row">
                        <button class="pc-btn pc-btn--sm" type="button" data-copy="<?= e($codigo) ?>" data-copy-ok="<?= e(t('ui.copied')) ?>">
                            <?= icon('copy', 16) ?>
                            <?= e(t('resultado.share_cta')) ?>
                        </button>

                        <button class="pc-btn pc-btn--sm pc-btn--ghost" type="button" data-share data-share-text="Meu perfil cinéfilo no PersonaCut: <?= e($codigo) ?>">
                            <?= icon('share', 16) ?>
                            <?= e(t('resultado.share_native')) ?>
                        </button>

                        <a class="pc-btn pc-btn--sm pc-btn--ghost" href="<?= url('quiz.php') ?>">
                            <?= icon('refresh', 16) ?>
                            <?= e(t('resultado.retake')) ?>
                        </a>
                    </div>
                </section>

                <section class="pc-compare-box">
                    <span class="pc-eyebrow"><?= e(t('resultado.compare_title')) ?></span>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.compare_subtitle')) ?></p>

                    <form action="<?= url('sinergia.php') ?>" method="get">
                        <input type="hidden" name="a" value="<?= e($codigo) ?>">
                        <label class="pc-label" for="outro-codigo"><?= e(t('resultado.compare_placeholder')) ?></label>
                        <input class="pc-input" id="outro-codigo" name="b" type="text" maxlength="8" required data-code-input placeholder="WXYZ6789">
                        <button class="pc-btn pc-btn--sm" type="submit">
                            <?= e(t('resultado.compare_cta')) ?>
                            <?= icon('users', 16) ?>
                        </button>
                    </form>
                </section>
            </div>

            <p class="pc-disclaimer">
                <?= e(t('resultado.disclaimer')) ?>
                · <?= e(t('resultado.rarity_note')) ?>
                · <?= e(t('tmdb.credito')) ?>
            </p>
        </div>
    </section>
