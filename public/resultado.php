<?php

declare(strict_types=1);

/** Dashboard de identidade cinéfila. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Controllers\PerfilController;
use Core\Database;
use Core\Radar;
use Services\LikertCalculatorService;
use Services\MatcherService;

$codigoParam = $_GET['codigo'] ?? '';
$codigo = is_string($codigoParam) ? strtoupper(trim($codigoParam)) : '';

$perfil = null;
$erro = null;

if ($codigo !== '') {
    try {
        $pdo = Database::pdo();
        $matcher = new MatcherService(
            (float) ($config['raridade']['limiar'] ?? 12.0),
            (int) ($config['raridade']['min_base'] ?? 100)
        );

        $perfil = (new PerfilController($pdo, $matcher, new LikertCalculatorService(), $config))
            ->montar($codigo);
    } catch (Throwable $erroCapturado) {
        $erro = $erroCapturado;
    }
}

$arquetipo = $perfil['arquetipo'] ?? null;
$tituloPerfil = $arquetipo['titulo'] ?? t('resultado.neutral_title');

$paginaTitulo = $perfil !== null
    ? $tituloPerfil . ' — PersonaCut'
    : t('resultado.title') . ' — PersonaCut';

$scriptsExtras = ['resultado.js', 'quickview.js', 'retrato.js', 'storycard.js'];
$vendorScripts = ['html2canvas.min.js'];
$temGroq = trim((string) ($config['groq']['api_key'] ?? '')) !== ''
    && (bool) ($config['groq']['enabled'] ?? false);

$dominantes = [];
$eixosPorId = [];
$radar = '';

if ($perfil !== null) {
    foreach ($perfil['eixos'] as $eixo) {
        $eixosPorId[(int) $eixo['id']] = $eixo;
    }

    foreach ([
        ['id' => $perfil['resolucao']['eixo_1'], 'polo' => $perfil['resolucao']['polo_1']],
        ['id' => $perfil['resolucao']['eixo_2'], 'polo' => $perfil['resolucao']['polo_2']],
    ] as $dominante) {
        if ($dominante['id'] === null) {
            continue;
        }

        $eixo = $eixosPorId[(int) $dominante['id']] ?? null;

        if ($eixo === null) {
            continue;
        }

        $ehDireita = $dominante['polo'] === 'D';
        $pct = null;

        foreach ($perfil['linhas'] as $linha) {
            if ((int) ($linha['eixo']['id'] ?? 0) === (int) $dominante['id']) {
                $pct = $ehDireita ? (float) $linha['pct_dir'] : (float) $linha['pct_esq'];
                break;
            }
        }

        $dominantes[] = [
            'nome' => eixo_nome($eixo),
            'polo' => eixo_polo($eixo, $ehDireita ? 'dir' : 'esq'),
            'cor'  => $ehDireita ? (string) $eixo['cor_dir'] : (string) $eixo['cor_esq'],
            'pct'  => $pct,
        ];
    }

    $radar = Radar::render(
        array_map(static fn (array $linha): string => eixo_nome($linha['eixo']), $perfil['linhas']),
        [[
            'nome' => t('resultado.title'),
            'valores' => array_map(static fn (array $linha): float => (float) $linha['pct_dir'], $perfil['linhas']),
            'cor' => 'var(--pc-primary)',
        ]],
        array_map(static fn (array $linha): string => (string) $linha['cor'], $perfil['linhas'])
    );

    $raridade = $perfil['raridade'];
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<?php if ($erro !== null): ?>
    <section class="pc-section">
        <div class="pc-wrap pc-wrap--narrow">
            <div class="pc-alert pc-alert--erro">
                <?= e($config['debug'] ? $erro->getMessage() : t('erros.generic')) ?>
            </div>
            <a class="pc-btn pc-btn--ghost pc-mt-3" href="<?= url('/') ?>"><?= e(t('ui.back_home')) ?></a>
        </div>
    </section>
<?php elseif ($perfil === null): ?>
    <section class="pc-section">
        <div class="pc-wrap pc-wrap--narrow">
            <span class="pc-eyebrow"><?= e(t('resultado.eyebrow')) ?></span>
            <h1 class="pc-title"><?= e($codigo === '' ? t('resultado.consult_title') : t('resultado.not_found_title')) ?></h1>
            <p class="pc-lead pc-mt-2">
                <?= e($codigo === '' ? t('resultado.consult_text') : t('resultado.not_found_text')) ?>
            </p>

            <form class="pc-mt-3" action="<?= url('resultado.php') ?>" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
                <div class="pc-field" style="flex:1;min-width:220px">
                    <label class="pc-label" for="codigo"><?= e(t('home.compare.label_a')) ?></label>
                    <input class="pc-input" id="codigo" name="codigo" type="text" maxlength="8" required data-code-input placeholder="7KQ2M9XA" autofocus>
                </div>
                <button class="pc-btn" type="submit"><?= e(t('resultado.consult_cta')) ?> <?= icon('arrow-right', 17) ?></button>
            </form>

            <a class="pc-link-arrow pc-mt-3" href="<?= url('quiz.php') ?>">
                <?= e(t('home.hero.cta_primary')) ?> <?= icon('arrow-right', 16) ?>
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="pc-result">
        <div class="pc-wrap">
            <div class="pc-result__head">
                <div>
                    <span class="pc-eyebrow"><?= e(t('resultado.eyebrow')) ?></span>
                    <h1 class="pc-title"><?= e(t('resultado.title')) ?></h1>
                </div>

                <div class="pc-result__code">
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

                    <?php if ($dominantes !== []): ?>
                        <div class="pc-profile-card__poles">
                            <?php foreach ($dominantes as $dominante): ?>
                                <span class="pc-chip">
                                    <i class="pc-chip__dot" style="background: <?= e($dominante['cor']) ?>"></i>
                                    <?= e($dominante['nome']) ?> · <?= e($dominante['polo']) ?>
                                    <?php if ($dominante['pct'] !== null): ?>
                                        <b class="pc-chip__pct"><?= pct((float) $dominante['pct']) ?></b>
                                    <?php endif; ?>
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
                        <div class="pc-ficha__item">
                            <span><?= e(t('resultado.ficha_rarity')) ?></span>
                            <b>
                                <?php if ($raridade['confiavel'] && $raridade['um_em'] !== null): ?>
                                    <?= e(t('resultado.rarity_one_in', ['n' => $raridade['um_em']])) ?>
                                <?php else: ?>
                                    <?= e(t('resultado.rarity_initial')) ?>
                                <?php endif; ?>
                            </b>
                        </div>
                        <div class="pc-ficha__item">
                            <span><?= e(t('resultado.ficha_decade')) ?></span>
                            <b><?= e($perfil['insights']['decada']['label'] ?? '—') ?></b>
                        </div>
                    </div>
                </div>

                <?php
                $storyChars = array_slice($perfil['personagens'], 0, 3);
                $storyFilms = array_slice($perfil['estante'], 0, 3);
                ?>
                <aside class="pc-profile-card__side pc-story-side">
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
                <section class="pc-panel pc-critico reveal" data-retrato data-codigo="<?= e($codigo) ?>" data-erro="<?= e(t('resultado.critic_error')) ?>">
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

            <div class="pc-result__grid">
                <section class="pc-panel reveal">
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

            <?php if ($perfil['retrato'] !== []): ?>
                <section class="pc-panel pc-retrato reveal">
                    <div class="pc-panel__head">
                        <div>
                            <h2><?= e(t('resultado.portrait_title')) ?></h2>
                            <p><?= e(t('resultado.portrait_subtitle')) ?></p>
                        </div>
                    </div>

                    <div class="pc-retrato__grid">
                        <?php foreach ($perfil['retrato'] as $item): ?>
                            <article class="pc-retrato__item">
                                <span class="pc-retrato__tag" style="--tag-color: <?= e($item['cor']) ?>">
                                    <?= e($item['eixo']) ?> · <?= e($item['polo']) ?> <?= pct($item['pct']) ?>
                                </span>
                                <p><?= e($item['texto']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

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
                    <?php if ($ins['distincao'] !== null): ?>
                        <article class="pc-insight reveal">
                            <span class="pc-insight__icon"><?= icon('compass', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.distincao_title')) ?></span>
                            <span class="pc-insight__value">
                                <i class="pc-insight__dot" style="background: <?= e($ins['distincao']['cor']) ?>"></i>
                                <?= e($ins['distincao']['eixo']) ?> · <?= e($ins['distincao']['polo']) ?>
                            </span>
                            <p><?= e(t('resultado.insights.distincao_text', [
                                'polo' => $ins['distincao']['polo'],
                                'pct' => numero($ins['distincao']['percentil'], 0),
                            ])) ?></p>
                        </article>
                    <?php endif; ?>

                    <?php if ($ins['tipico'] !== null): ?>
                        <article class="pc-insight reveal">
                            <span class="pc-insight__icon"><?= icon('scale', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.tipico_title')) ?></span>
                            <span class="pc-insight__value"><?= e($ins['tipico']['eixo']) ?> · <?= pct($ins['tipico']['pct']) ?></span>
                            <p><?= e(t('resultado.insights.tipico_text', ['eixo' => $ins['tipico']['eixo']])) ?></p>
                        </article>
                    <?php endif; ?>

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
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
