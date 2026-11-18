<?php

declare(strict_types=1);

/** Questionário: escolha de formato e teste interativo. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Core\Csrf;
use Models\Eixo;
use Models\EixoGuia;
use Models\Pergunta;

$modoParam = $_GET['modo'] ?? null;
$modo = is_string($modoParam) && isset(Pergunta::MODOS[$modoParam]) ? $modoParam : null;

$paginaTitulo = $modo !== null
    ? t('quiz.eyebrow') . ' — PersonaCut'
    : t('home.formats.title') . ' — PersonaCut';

$navSimples = true;
$rodapeSimples = true;
$scriptsExtras = [];
$perguntas = [];
$erroBanco = null;

if ($modo !== null) {
    try {
        $pdo = Database::pdo();
        $perguntas = Pergunta::paraModo($pdo, $modo);
        $eixos = Eixo::todos($pdo);
    } catch (Throwable $erro) {
        $erroBanco = 'Não foi possível carregar as perguntas. Verifique se o banco está no ar (docker compose up -d).';
        $eixos = [];
    }
}

// Mapa id => eixo, para enriquecer o payload do quiz.
$eixosPorId = [];
foreach ($eixos ?? [] as $eixo) {
    $eixosPorId[(int) $eixo['id']] = $eixo;
}

$configQuiz = null;

if ($modo !== null && $perguntas !== []) {
    $lista = [];

    foreach ($perguntas as $pergunta) {
        $eixo = $eixosPorId[(int) $pergunta['eixo_id']] ?? null;
        $slug = (string) ($eixo['slug'] ?? 'estetica');

        $lista[] = [
            'id'          => (int) $pergunta['id'],
            'texto'       => (string) $pergunta['texto'],
            'eixo'        => eixo_nome($eixo ?? []),
            'slug'        => $slug,
            'cor'         => (string) ($eixo['cor_dir'] ?? '#0c3b2e'),
            'icone'       => icon((string) ($eixo['icone_dir'] ?? 'sparkles'), 15),
            'midia'       => midia_pergunta($pergunta),
        ];
    }

    // Guia completo de cada eixo, usado no modal "Entender este eixo".
    $guias = [];

    foreach ($eixos as $eixo) {
        $slug = (string) ($eixo['slug'] ?? '');
        $guia = EixoGuia::porSlug($slug);

        $guias[$slug] = [
            'nome'       => eixo_nome($eixo),
            'poloEsq'    => eixo_polo($eixo, 'esq'),
            'poloDir'    => eixo_polo($eixo, 'dir'),
            'corEsq'     => (string) ($eixo['cor_esq'] ?? '#8c5a2b'),
            'corDir'     => (string) ($eixo['cor_dir'] ?? '#4f6480'),
            'icone'      => icon((string) ($eixo['icone_dir'] ?? 'sparkles'), 18),
            'resumo'     => (string) ($guia['resumo'] ?? ''),
            'paragrafos' => $guia['paragrafos'] ?? [],
            'leitura'    => $guia['leitura'] ?? ['esq' => '', 'dir' => ''],
            'cenas'      => $guia['cenas'] ?? [],
            'citacoes'   => $guia['citacoes'] ?? [],
        ];
    }

    $configQuiz = [
        'modo'         => $modo,
        'endpoint'     => url('api/quiz.php'),
        'resultadoUrl' => url('resultado.php'),
        'csrf'         => Csrf::token(),
        'perguntas'    => $lista,
        'guias'        => $guias,
        'textos'       => [
            'next'       => t('quiz.next'),
            'finish'     => t('quiz.finish'),
            'incomplete' => t('quiz.incomplete'),
            'error'      => t('quiz.error'),
            'restart'    => t('quiz.restart'),
            'kicker'     => t('quiz.guide_kicker'),
            'title'      => t('quiz.guide_title'),
            'film'       => t('quiz.guide_film'),
            'series'     => t('quiz.guide_series'),
        ],
    ];

    $scriptsExtras = ['quiz.js'];
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<?php if ($modo === null): ?>
    <section class="pc-section">
        <div class="pc-wrap">
            <div class="pc-section__head">
                <div>
                    <span class="pc-eyebrow"><?= e(t('home.formats.eyebrow')) ?></span>
                    <h1 class="pc-title"><?= e(t('home.formats.title')) ?></h1>
                </div>
                <p class="pc-lead"><?= e(t('home.formats.subtitle')) ?></p>
            </div>

            <div class="pc-formats">
                <article class="pc-format">
                    <h2 class="pc-format__label"><?= e(t('home.formats.quick.label')) ?></h2>
                    <div class="pc-format__count">
                        <b><?= e(t('home.formats.quick.count')) ?></b>
                        <span><?= e(t('home.formats.quick.unit')) ?></span>
                    </div>
                    <p><?= e(t('home.formats.quick.text')) ?></p>
                    <hr class="pc-format__rule">
                    <div class="pc-format__meta"><?= icon('clock', 15) ?> <?= e(t('home.formats.quick.time')) ?></div>
                    <a class="pc-btn pc-btn--ghost" href="<?= url('quiz.php', ['modo' => 'rapido']) ?>">
                        <span class="pc-btn__label"><?= e(t('home.formats.quick.cta')) ?></span>
                        <?= icon('arrow-right', 17) ?>
                    </a>
                </article>

                <article class="pc-format pc-format--featured">
                    <span class="pc-format__recommended"><?= e(t('home.formats.recommended')) ?></span>
                    <h2 class="pc-format__label"><?= e(t('home.formats.full.label')) ?></h2>
                    <div class="pc-format__count">
                        <b><?= e(t('home.formats.full.count')) ?></b>
                        <span><?= e(t('home.formats.full.unit')) ?></span>
                    </div>
                    <p><?= e(t('home.formats.full.text')) ?></p>
                    <hr class="pc-format__rule">
                    <div class="pc-format__meta"><?= icon('clock', 15) ?> <?= e(t('home.formats.full.time')) ?></div>
                    <a class="pc-btn pc-btn--light" href="<?= url('quiz.php', ['modo' => 'completo']) ?>">
                        <span class="pc-btn__label"><?= e(t('home.formats.full.cta')) ?></span>
                        <?= icon('arrow-right', 17) ?>
                    </a>
                </article>

                <article class="pc-format">
                    <h2 class="pc-format__label"><?= e(t('home.formats.extreme.label')) ?></h2>
                    <div class="pc-format__count">
                        <b><?= e(t('home.formats.extreme.count')) ?></b>
                        <span><?= e(t('home.formats.extreme.unit')) ?></span>
                    </div>
                    <p><?= e(t('home.formats.extreme.text')) ?></p>
                    <hr class="pc-format__rule">
                    <div class="pc-format__meta"><?= icon('clock', 15) ?> <?= e(t('home.formats.extreme.time')) ?></div>
                    <a class="pc-btn pc-btn--ghost" href="<?= url('quiz.php', ['modo' => 'extremo']) ?>">
                        <span class="pc-btn__label"><?= e(t('home.formats.extreme.cta')) ?></span>
                        <?= icon('arrow-right', 17) ?>
                    </a>
                </article>
            </div>

            <p class="pc-formats__note"><?= e(t('home.formats.note')) ?></p>
        </div>
    </section>
<?php elseif ($erroBanco !== null): ?>
    <section class="pc-section">
        <div class="pc-wrap pc-wrap--narrow">
            <div class="pc-alert pc-alert--erro"><?= e($erroBanco) ?></div>
            <a class="pc-btn pc-btn--ghost pc-mt-3" href="<?= url('/') ?>"><?= e(t('ui.back_home')) ?></a>
        </div>
    </section>
<?php else: ?>
    <section class="pc-quiz">
        <div class="pc-wrap">
            <div class="pc-quiz__progress">
                <div class="pc-quiz__progress-meta">
                    <span><?= e(t('quiz.progress_label')) ?> <b data-q-atual>1</b> <?= e(t('quiz.of')) ?> <b><?= count($perguntas) ?></b></span>
                    <span data-q-pct>0% concluído</span>
                </div>
                <div class="pc-segments" data-segments role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Progresso do teste"></div>
            </div>

            <article class="pc-question" aria-live="polite">
                <div class="pc-question__body">
                    <span class="pc-question__num" data-q-num aria-hidden="true">01</span>

                    <div class="pc-question__media" data-q-media-col>
                        <figure class="pc-scene" data-q-media hidden>
                            <div class="pc-scene__frame" data-q-media-frame></div>
                            <figcaption class="pc-scene__caption">
                                <span class="pc-scene__title" data-q-media-caption></span>
                                <span class="pc-scene__credit" data-q-media-credit></span>
                            </figcaption>
                        </figure>
                    </div>

                    <div class="pc-question__main">
                        <div class="pc-question__head">
                            <p class="pc-axis-tag" data-q-tag>
                                <span class="pc-axis-tag__icon" data-q-axis-icon aria-hidden="true">
                                    <?= icon((string) ($eixosPorId[(int) ($perguntas[0]['eixo_id'] ?? 0)]['icone_dir'] ?? 'sparkles'), 15) ?>
                                </span>
                                <span data-q-axis><?= e(eixo_nome($eixosPorId[(int) ($perguntas[0]['eixo_id'] ?? 0)] ?? [])) ?></span>
                            </p>

                            <button class="pc-understand" type="button" data-guide-open aria-haspopup="dialog">
                                <?= icon('compass', 14) ?>
                                <?= e(t('quiz.understand')) ?>
                            </button>
                        </div>

                        <h1 class="pc-question__text" data-q-text><?= e((string) ($perguntas[0]['texto'] ?? '')) ?></h1>

                        <div class="pc-answers" role="radiogroup" aria-label="Opções de resposta" data-answers>
                            <?php foreach (tl('quiz.answers') as $resposta): ?>
                                <?php
                                $valor = (int) $resposta['value'];
                                $icone = match (true) {
                                    $valor === 2, $valor === 1 => 'check',
                                    $valor === 0 => 'minus',
                                    default => 'x',
                                };
                                ?>
                                <button
                                    class="pc-answer"
                                    type="button"
                                    role="radio"
                                    aria-checked="false"
                                    data-value="<?= $valor ?>"
                                >
                                    <span class="pc-answer__icon" aria-hidden="true"><?= icon($icone, 15) ?></span>
                                    <span><?= e($resposta['label']) ?></span>
                                    <span class="pc-answer__tail" aria-hidden="true"></span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="pc-quiz__actions">
                            <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" data-quiz-back disabled>
                                <?= icon('arrow-left', 16) ?>
                                <?= e(t('quiz.back')) ?>
                            </button>

                            <label class="pc-switch">
                                <input type="checkbox" data-auto-advance checked>
                                <span class="pc-switch__track" aria-hidden="true"></span>
                                <?= e(t('quiz.auto_advance')) ?>
                            </label>

                            <button class="pc-btn pc-btn--sm" type="button" data-quiz-next disabled>
                                <span data-next-label><?= e(t('quiz.next')) ?></span>
                                <?= icon('arrow-right', 16) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            <p class="pc-quiz__kbd">
                <span class="pc-kbd">1</span>
                <span class="pc-kbd">2</span>
                <span class="pc-kbd">3</span>
                <span class="pc-kbd">4</span>
                <span class="pc-kbd">5</span>
                &nbsp;<?= e(t('quiz.keyboard_hint')) ?>
            </p>

            <p class="pc-alert pc-alert--erro pc-mt-2" data-quiz-error hidden role="alert"></p>
        </div>

        <div class="pc-quiz__loading" data-loading aria-hidden="true">
            <div class="pc-loading-card">
                <div class="pc-spinner"></div>
                <h3><?= e(t('quiz.saving')) ?></h3>
                <p><?= e(t('quiz.saving_hint')) ?></p>
            </div>
        </div>

        <dialog class="pc-guide" data-guide aria-labelledby="quiz-guide-title">
            <div class="pc-guide__top">
                <span class="pc-guide__kicker" data-guide-kicker></span>
                <button class="pc-guide__close" type="button" data-guide-close aria-label="<?= e(t('quiz.guide_close')) ?>">
                    <?= icon('x', 16) ?>
                </button>
            </div>

            <div class="pc-guide__scroll" tabindex="0">
                <header class="pc-guide__head">
                    <span class="pc-guide__icon" data-guide-icon aria-hidden="true"></span>
                    <h2 class="pc-guide__title" id="quiz-guide-title" data-guide-title></h2>
                    <p class="pc-guide__resumo" data-guide-resumo></p>
                </header>

                <div class="pc-guide__poles" data-guide-poles></div>

                <div class="pc-guide__prose" data-guide-prose></div>

                <section class="pc-guide__section">
                    <h3><?= e(t('quiz.guide_scenes')) ?></h3>
                    <ul class="pc-guide__scenes" data-guide-scenes></ul>
                </section>

                <section class="pc-guide__section">
                    <h3><?= e(t('quiz.guide_refs')) ?></h3>
                    <ul class="pc-guide__cites" data-guide-cites></ul>
                </section>

                <section class="pc-guide__section">
                    <h3><?= e(t('quiz.guide_score')) ?></h3>
                    <div class="pc-guide__score" data-guide-leitura></div>
                </section>
            </div>

            <footer class="pc-guide__foot">
                <button class="pc-btn pc-btn--sm" type="button" data-guide-close><?= e(t('quiz.guide_close')) ?></button>
            </footer>
        </dialog>

        <noscript>
            <div class="pc-wrap pc-mt-3">
                <div class="pc-alert pc-alert--erro">O teste precisa de JavaScript para funcionar.</div>
            </div>
        </noscript>

        <script type="application/json" id="quiz-config"><?= json_encode($configQuiz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
