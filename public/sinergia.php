<?php

declare(strict_types=1);

/** Modo Sinergia: compatibilidade entre dois perfis. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Controllers\PerfilController;
use Core\Database;
use Core\Radar;
use Services\LikertCalculatorService;
use Services\MatcherService;

$codigoA = is_string($_GET['a'] ?? null) ? strtoupper(trim((string) $_GET['a'])) : '';
$codigoB = is_string($_GET['b'] ?? null) ? strtoupper(trim((string) $_GET['b'])) : '';

$comparado = null;
$erro = null;
$tentou = $codigoA !== '' && $codigoB !== '';

if ($tentou) {
    try {
        $pdo = Database::pdo();
        $matcher = new MatcherService(
            (float) ($config['raridade']['limiar'] ?? 12.0),
            (int) ($config['raridade']['min_base'] ?? 100)
        );

        $comparado = (new PerfilController($pdo, $matcher, new LikertCalculatorService(), $config))
            ->comparar($codigoA, $codigoB);
    } catch (Throwable $erroCapturado) {
        $erro = $erroCapturado;
    }
}

$paginaTitulo = t('sinergia.title') . ' — PersonaCut';
$paginaDescricao = t('sinergia.subtitle');

$radarSvg = '';

if ($comparado !== null) {
    $linhasA = $comparado['a']['linhas'];
    $linhasB = $comparado['b']['linhas'];
    $nomeA = $comparado['a']['arquetipo']['titulo'] ?? t('resultado.neutral_title');
    $nomeB = $comparado['b']['arquetipo']['titulo'] ?? t('resultado.neutral_title');

    $radarSvg = Radar::render(
        array_map(static fn (array $linha): string => eixo_nome($linha['eixo']), $linhasA),
        [
            ['nome' => $nomeA, 'valores' => array_column($linhasA, 'pct_dir'), 'cor' => 'var(--pc-primary)'],
            ['nome' => $nomeB, 'valores' => array_column($linhasB, 'pct_dir'), 'cor' => 'var(--pc-red)'],
        ]
    );
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<section class="pc-result">
    <div class="pc-wrap">
        <div class="pc-result__head">
            <div>
                <span class="pc-eyebrow"><?= e(t('sinergia.eyebrow')) ?></span>
                <h1 class="pc-title"><?= e(t('sinergia.title')) ?></h1>
                <p class="pc-lead pc-mt-2"><?= e(t('sinergia.subtitle')) ?></p>
            </div>

            <a class="pc-btn pc-btn--ghost pc-btn--sm" href="<?= url('/') ?>#comparar">
                <?= icon('users', 16) ?>
                <?= e(t('sinergia.new_compare')) ?>
            </a>
        </div>

        <?php if ($erro !== null): ?>
            <div class="pc-alert pc-alert--erro">
                <?= e($config['debug'] ? $erro->getMessage() : t('erros.generic')) ?>
            </div>
        <?php elseif ($comparado === null): ?>
            <div class="pc-compare-box" style="max-width:720px">
                <span class="pc-eyebrow"><?= e(t('sinergia.error_title')) ?></span>
                <p class="pc-lead" style="font-size:14px"><?= e(t('sinergia.error_text')) ?></p>

                <form action="<?= url('sinergia.php') ?>" method="get" style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;margin-top:18px">
                    <div class="pc-field">
                        <label class="pc-label" for="a"><?= e(t('home.compare.label_a')) ?></label>
                        <input class="pc-input" id="a" name="a" type="text" maxlength="8" required data-code-input value="<?= e($codigoA) ?>">
                    </div>
                    <div class="pc-field">
                        <label class="pc-label" for="b"><?= e(t('home.compare.label_b')) ?></label>
                        <input class="pc-input" id="b" name="b" type="text" maxlength="8" required data-code-input value="<?= e($codigoB) ?>">
                    </div>
                    <button class="pc-btn" type="submit"><?= e(t('home.compare.cta')) ?></button>
                </form>
            </div>
        <?php else: ?>
            <?php $comparacao = $comparado['comparacao']; ?>
            <div class="pc-pair">
                <a class="pc-pair-card" href="<?= url('resultado.php', ['codigo' => $codigoA]) ?>">
                    <span class="pc-monogram"><?= e(iniciais((string) ($comparado['a']['arquetipo']['titulo'] ?? 'Neutro'))) ?></span>
                    <span>
                        <span class="pc-mini-card__name"><?= e($comparado['a']['arquetipo']['titulo'] ?? t('resultado.neutral_title')) ?></span>
                        <span class="pc-mini-card__meta"><?= e($codigoA) ?> · <?= e(t('sinergia.open_a')) ?></span>
                    </span>
                </a>

                <a class="pc-pair-card" href="<?= url('resultado.php', ['codigo' => $codigoB]) ?>">
                    <span class="pc-monogram" style="background: var(--pc-red)"><?= e(iniciais((string) ($comparado['b']['arquetipo']['titulo'] ?? 'Neutro'))) ?></span>
                    <span>
                        <span class="pc-mini-card__name"><?= e($comparado['b']['arquetipo']['titulo'] ?? t('resultado.neutral_title')) ?></span>
                        <span class="pc-mini-card__meta"><?= e($codigoB) ?> · <?= e(t('sinergia.open_b')) ?></span>
                    </span>
                </a>
            </div>

            <div class="pc-compat">
                <aside class="pc-compat__score">
                    <span class="pc-compat__label"><?= e(t('sinergia.compat')) ?></span>
                    <span class="pc-compat__value"><?= pct($comparacao['compatibilidade']) ?></span>
                    <span class="pc-compat__verdict"><?= e(t('sinergia.verdicts.' . $comparacao['veredito'])) ?></span>
                </aside>

                <section class="pc-panel pc-compat__chart">
                    <div class="pc-radar-wrap pc-radar-wrap--pair"><?= $radarSvg ?></div>
                    <p class="pc-radar__caption"><?= e(t('sinergia.radar_caption')) ?></p>
                </section>
            </div>

            <div class="pc-result__grid">
                <section class="pc-panel">
                    <div class="pc-panel__head">
                        <div>
                            <h2><?= e(t('sinergia.convergence')) ?></h2>
                            <p><?= e(t('sinergia.convergence_sub')) ?></p>
                        </div>
                    </div>

                    <div class="pc-conv">
                        <?php foreach ($comparacao['convergencia'] as $indice): ?>
                            <?php $linha = $comparado['a']['linhas'][$indice]; $dif = (float) $comparacao['diferencas'][$indice]; ?>
                            <div class="pc-conv__row">
                                <span class="pc-conv__name"><?= e(eixo_nome($linha['eixo'])) ?></span>
                                <span class="pc-conv__bar"><i style="width: <?= num_css(max(0, 100 - $dif)) ?>%; background: <?= e($linha['cor_dir']) ?>"></i></span>
                                <span class="pc-conv__value"><?= numero($dif, 0) ?> pts</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="pc-panel">
                    <div class="pc-panel__head">
                        <div>
                            <h2><?= e(t('sinergia.divergence')) ?></h2>
                            <p><?= e(t('sinergia.divergence_sub')) ?></p>
                        </div>
                    </div>

                    <div class="pc-conv">
                        <?php foreach ($comparacao['divergencia'] as $indice): ?>
                            <?php $linha = $comparado['a']['linhas'][$indice]; $dif = (float) $comparacao['diferencas'][$indice]; ?>
                            <div class="pc-conv__row pc-conv--div">
                                <span class="pc-conv__name"><?= e(eixo_nome($linha['eixo'])) ?></span>
                                <span class="pc-conv__bar"><i style="width: <?= num_css($dif) ?>%"></i></span>
                                <span class="pc-conv__value"><?= numero($dif, 0) ?> pts</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <section class="pc-section" style="padding-bottom:0">
                <div class="pc-section__head">
                    <div>
                        <span class="pc-eyebrow"><?= e(t('sinergia.combined_eyebrow')) ?></span>
                        <h2 class="pc-title"><?= e(t('sinergia.combined_title')) ?></h2>
                        <p class="pc-lead pc-mt-1" style="font-size:15px"><?= e(t('sinergia.combined_subtitle')) ?></p>
                    </div>
                </div>

                <div class="pc-two-col">
                    <?php foreach ([
                        ['item' => $comparado['combinado']['personagem'], 'tipo' => 'personagem'],
                        ['item' => $comparado['combinado']['estudio'], 'tipo' => 'estudio'],
                    ] as $bloco): ?>
                        <?php if ($bloco['item'] === null): ?>
                            <?php continue; ?>
                        <?php endif; ?>

                        <?php
                        $item = $bloco['item'];
                        $cor = cor_dominante($item['vetor'], $comparado['a']['eixos']);
                        $ehEstudio = $bloco['tipo'] === 'estudio';
                        ?>
                        <article class="pc-entity pc-entity--rich pc-entity--anchor">
                            <div class="pc-entity__poster <?= $ehEstudio ? 'pc-entity__poster--logo' : '' ?>">
                                <?php if (!empty($item['imagem_url'])): ?>
                                    <img src="<?= e($item['imagem_url']) ?>" alt="" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <span class="pc-entity__mono-lg" style="--mono-bg: <?= e($cor) ?>"><?= e(iniciais((string) $item['nome'])) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="pc-entity__content">
                                <div class="pc-entity__name"><?= e($item['nome']) ?></div>
                                <div class="pc-entity__meta">
                                    <?= e($item['obra_origem']) ?><?= $item['ano'] !== null ? ' · ' . (int) $item['ano'] : '' ?>
                                </div>
                                <p class="pc-entity__bio"><?= e((string) ($item['bio'] ?? '')) ?></p>

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

                <p class="pc-disclaimer"><?= e(t('sinergia.how_text')) ?></p>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
