<?php

declare(strict_types=1);

/** Landing page do PersonaCut. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Models\Eixo;

// Os eixos vêm do banco; se o banco ainda não estiver pronto, a página usa
// uma lista estática para não quebrar a apresentação.
$eixos = [];
$eixosDoBanco = true;

try {
    $eixos = Eixo::todos(Core\Database::pdo());
} catch (Throwable $erro) {
    $eixosDoBanco = false;
    $slugs = ['estetica', 'tom', 'universo', 'ritmo', 'moralidade', 'estrutura', 'atmosfera', 'epoca'];
    $icones = ['frame', 'moon', 'building', 'feather', 'scale', 'list', 'home', 'film'];
    $cores = [['#A9762F', '#6C63B5'], ['#4F7399', '#D9A441'], ['#4F7F5F', '#9B5BA6'], ['#4E8493', '#C2553A'], ['#6E6A9E', '#B98A2E'], ['#4E8080', '#A84F6B'], ['#9C6F82', '#3F7FA8'], ['#9A7B4F', '#479184']];

    foreach ($slugs as $i => $slug) {
        $eixos[] = [
            'id' => $i + 1,
            'slug' => $slug,
            'ordem' => $i + 1,
            'nome' => t('eixo.' . $slug . '.nome'),
            'descricao' => t('eixo.' . $slug . '.desc'),
            'polo_esq_key' => 'eixo.' . $slug . '.esq',
            'polo_esq_nome' => t('eixo.' . $slug . '.esq'),
            'polo_dir_key' => 'eixo.' . $slug . '.dir',
            'polo_dir_nome' => t('eixo.' . $slug . '.dir'),
            'cor_esq' => $cores[$i][0],
            'cor_dir' => $cores[$i][1],
            'icone_esq' => $icones[$i],
            'icone_dir' => $icones[$i],
        ];
    }
}

$paginaTitulo = t('meta.title');
$paginaDescricao = t('meta.description');

/*
 * Cards de exemplo da landing: usa perfis da base sintética, montados pelo mesmo
 * PerfilController do resultado. EXEMPLO1/2/3 são curados no seed; se o banco
 * ainda não tiver os três, completa com outros sintéticos. Sem banco, cai num
 * exemplo fixo.
 */
$exemplo = [
    'codigo' => '',
    'titulo' => 'O Espectador Neutro',
    'descricao' => 'Um perfil equilibrado, que transita entre os dois polos de cada eixo.',
    'afinidade' => 0.0,
    // Dados usados pelo card 9:16 e pelo painel de leituras.
    'personagens' => [],
    'leituras' => [],
    'estante' => [],
    'diretor' => null,
    'estudio' => null,
];

$montarExemplo = static function (string $codigo) use ($config): ?array {
    $perfilExemplo = (new Controllers\PerfilController(
        Core\Database::pdo(),
        new Services\MatcherService(
            (float) ($config['raridade']['limiar'] ?? 12.0),
            (int) ($config['raridade']['min_base'] ?? 100)
        ),
        new Services\LikertCalculatorService(),
        $config
    ))->montar($codigo, true, 'misturado');

    if ($perfilExemplo === null) {
        return null;
    }

    $arquetipoExemplo = $perfilExemplo['arquetipo'];

    return [
        'codigo' => $codigo,
        'titulo' => (string) ($arquetipoExemplo['titulo'] ?? 'O Espectador Neutro'),
        'descricao' => (string) ($arquetipoExemplo['descricao'] ?? ''),
        'afinidade' => (float) ($perfilExemplo['afinidade_media'] ?? 0),
        // Repassa os dados brutos para o card 9:16 de compartilhamento.
        'personagens' => $perfilExemplo['personagens'] ?? [],
        'filmes_top' => $perfilExemplo['filmes_top'] ?? [],
        'leituras' => $perfilExemplo['leituras'] ?? [],
        'estante' => $perfilExemplo['estante'] ?? [],
        'diretor' => $perfilExemplo['diretor'] ?? null,
        'estudio' => $perfilExemplo['estudio'] ?? null,
    ];
};

$exemplos = [];

try {
    $pdoExemplo = Core\Database::pdo();
    $codigosExemplo = $pdoExemplo->query(
        "SELECT codigo_unico
           FROM resultados_usuarios
          WHERE codigo_unico IN ('EXEMPLO1', 'EXEMPLO2', 'EXEMPLO3')
          ORDER BY FIELD(codigo_unico, 'EXEMPLO1', 'EXEMPLO2', 'EXEMPLO3')"
    )->fetchAll(PDO::FETCH_COLUMN);

    // Bancos antigos podem ter só o EXEMPLO1: completa com sintéticos.
    if (count($codigosExemplo) < 3) {
        $faltam = 3 - count($codigosExemplo);
        $marcadores = $codigosExemplo === []
            ? "''"
            : implode(', ', array_fill(0, count($codigosExemplo), '?'));
        $consulta = $pdoExemplo->prepare(
            "SELECT codigo_unico
               FROM resultados_usuarios
              WHERE origem = 'sintetico'
                AND codigo_unico NOT IN ({$marcadores})
              ORDER BY id
              LIMIT " . (int) $faltam
        );
        $consulta->execute($codigosExemplo);
        $codigosExemplo = array_merge($codigosExemplo, $consulta->fetchAll(PDO::FETCH_COLUMN));
    }

    foreach (array_slice($codigosExemplo, 0, 3) as $codigoExemplo) {
        $item = $montarExemplo((string) $codigoExemplo);

        if ($item !== null) {
            $exemplos[] = $item;
        }
    }

    if ($exemplos !== []) {
        $exemplo = $exemplos[0];
    }
} catch (Throwable $erro) {
    // Landing continua com o exemplo fixo.
}

if ($exemplos === []) {
    $exemplos = [$exemplo];
}

$vendorStyles = ['swiper/swiper-bundle.min.css'];
$vendorScripts = ['swiper/swiper-bundle.min.js'];
$scriptsExtras = ['hero-phones.js'];

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<div class="pc-marquee" aria-hidden="true">
    <div class="pc-marquee__track">
        <?php for ($volta = 0; $volta < 2; $volta++): ?>
            <span>PersonaCut</span><i>✦</i>
            <span>6 eixos</span><i>✦</i>
            <span>Um perfil</span><i>✦</i>
            <span>Cinema analógico</span><i>✦</i>
            <span>Sem cadastro</span><i>✦</i>
            <span>Resultado imediato</span><i>✦</i>
        <?php endfor; ?>
    </div>
</div>

<section class="pc-hero">
    <div class="pc-wrap pc-hero__grid">
        <div class="reveal">
            <span class="pc-eyebrow"><?= e(t('home.hero.eyebrow')) ?></span>
            <h1 class="pc-hero__title"><?= t('home.hero.title') ?></h1>
            <p class="pc-hero__sub"><?= e(t('home.hero.subtitle')) ?></p>

            <div class="pc-hero__cta">
                <a class="pc-btn" href="<?= url('quiz.php') ?>">
                    <?= e(t('home.hero.cta_primary')) ?>
                    <?= icon('arrow-right', 18) ?>
                </a>
                <a class="pc-btn pc-btn--ghost" href="#eixos">
                    <?= e(t('home.hero.cta_secondary')) ?>
                </a>
            </div>

            <div class="pc-hero__meta">
                <?php foreach (tl('home.hero.meta') as $item): ?>
                    <span><?= e($item) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="reveal pc-hero__story">
            <div class="pc-hero-slider" data-phone-slider>
                <div class="pc-hero-slider__track" data-phone-track>
                    <?php foreach ($exemplos as $exemploSlide): ?>
                        <div class="pc-hero-slider__slide">
                            <?php
                            /*
                             * Card 9:16 idêntico ao gerado no questionário: reaproveita
                             * o parcial do resultado, alimentado por um perfil de exemplo.
                             * O escopo isolado evita que as variáveis vazem para a página.
                             */
                            (static function (array $exemplo): void {
                                $codigo = $exemplo['codigo'];
                                $tituloPerfil = $exemplo['titulo'];
                                $perfil = [
                                    'afinidade_media' => $exemplo['afinidade'],
                                    'leituras' => $exemplo['leituras'],
                                    'personagens' => $exemplo['personagens'],
                                    'filmes_top' => $exemplo['filmes_top'] ?? [],
                                    'estante' => $exemplo['estante'],
                                    'diretor' => $exemplo['diretor'],
                                    'estudio' => $exemplo['estudio'],
                                ];
                                require __DIR__ . '/partials/resultado/phone-frame.php';
                            })($exemploSlide);
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($exemplos) > 1): ?>
                    <button class="pc-hero-slider__nav pc-hero-slider__nav--prev" type="button"
                            data-phone-prev aria-label="Perfil anterior">
                        <?= icon('arrow-left', 15) ?>
                    </button>
                    <button class="pc-hero-slider__nav pc-hero-slider__nav--next" type="button"
                            data-phone-next aria-label="Próximo perfil">
                        <?= icon('arrow-right', 15) ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (count($exemplos) > 1): ?>
                <div class="pc-hero-slider__dots" data-phone-pagination aria-hidden="true"></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="pc-section">
    <div class="pc-wrap">
        <div class="pc-section__head reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.discover.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.discover.title')) ?></h2>
            </div>
            <p class="pc-lead"><?= e(t('home.discover.subtitle')) ?></p>
        </div>

        <div class="pc-features">
            <?php foreach (tl('home.discover.cards') as $card): ?>
                <article class="pc-feature reveal">
                    <span class="pc-feature__icon"><?= icon((string) $card['icon'], 21) ?></span>
                    <div>
                        <h3><?= e($card['title']) ?></h3>
                        <p><?= e($card['text']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pc-section pc-example">
    <div class="pc-wrap">
        <div class="pc-example__grid">
            <div class="reveal">
                <span class="pc-eyebrow"><?= e(t('home.example.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.example.title')) ?></h2>
                <p class="pc-lead pc-mt-2"><?= e(t('home.example.note')) ?></p>
                <a class="pc-link-arrow pc-mt-3" href="<?= url('quiz.php') ?>">
                    <?= e(t('home.example.cta')) ?>
                    <?= icon('arrow-right', 16) ?>
                </a>
            </div>

            <div class="reveal">
                <div class="pc-example__panel">
                    <div class="pc-example__panel-head">
                        <?php $exemploFilme = $exemplo['filmes_top'][0] ?? null; ?>
                        <div>
                            <span class="pc-example__badge"><?= icon('star', 12) ?> <?= e(t('resultado.films_eyebrow')) ?></span>
                            <h3 class="pc-example__name"><?= e($exemplo['titulo']) ?></h3>
                        </div>
                        <?php if ($exemploFilme !== null): ?>
                            <span class="pc-chip pc-chip--red"><?= e(t('resultado.match_top', [
                                'pct' => (int) ($exemploFilme['top_pct'] ?? 0),
                            ])) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($exemplo['leituras'] === []): ?>
                        <p class="pc-text-muted" style="font-size:13px">
                            Rode o seed para ver um perfil de exemplo montado com dados reais.
                        </p>
                    <?php endif; ?>

                    <div class="pc-example__readings">
                        <?php foreach ($exemplo['leituras'] as $indice => $leitura): ?>
                            <div class="pc-example__reading" style="--reading:<?= e($leitura['cor']) ?>">
                                <span class="pc-example__reading-num"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <div>
                                    <b><?= e($leitura['curto']) ?></b>
                                    <small><?= e($leitura['manchete']) ?></small>
                                </div>
                                <span class="pc-example__reading-forca"><?= e($leitura['forca']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($exemplo['codigo'] !== ''): ?>
                        <a class="pc-link-arrow" style="margin-top:18px" href="<?= url('resultado.php', ['codigo' => $exemplo['codigo']]) ?>">
                            <?= e(t('home.example.open')) ?> <?= icon('arrow-right', 15) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pc-section pc-dark" id="como-funciona">
    <div class="pc-wrap">
        <div class="reveal" style="max-width:640px">
            <span class="pc-eyebrow"><?= e(t('home.how.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('home.how.title')) ?></h2>
            <p class="pc-lead pc-mt-2"><?= e(t('home.how.subtitle')) ?></p>
        </div>

        <div class="pc-steps reveal">
            <?php foreach (tl('home.how.steps') as $i => $passo): ?>
                <article class="pc-step">
                    <span class="pc-step__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <h3><?= e($passo['title']) ?></h3>
                    <p><?= e($passo['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pc-section" id="eixos">
    <div class="pc-wrap">
        <div class="pc-section__head reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.axes.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.axes.title')) ?></h2>
            </div>
            <p class="pc-lead"><?= e(t('home.axes.subtitle')) ?></p>
        </div>

        <div class="pc-axes">
            <?php foreach ($eixos as $i => $eixo): ?>
                <article class="pc-axis-row reveal">
                    <span class="pc-axis-row__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="pc-axis-row__icon"><?= icon((string) $eixo['icone_esq'], 20) ?></span>
                    <div>
                        <h3><?= e(eixo_nome($eixo)) ?></h3>
                        <div class="pc-axis-row__poles">
                            <i class="pc-pole-dot" style="background: <?= e($eixo['cor_esq']) ?>"></i>
                            <span><?= e(eixo_polo($eixo, 'esq')) ?></span>
                            <span aria-hidden="true">↔</span>
                            <i class="pc-pole-dot" style="background: <?= e($eixo['cor_dir']) ?>"></i>
                            <span><?= e(eixo_polo($eixo, 'dir')) ?></span>
                        </div>
                        <p><?= e(eixo_descricao($eixo)) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (!$eixosDoBanco): ?>
            <p class="pc-hint pc-text-center pc-mt-3">Banco de dados indisponível: exibindo a definição estática dos eixos.</p>
        <?php endif; ?>
    </div>
</section>

<section class="pc-section" id="formatos" style="background: var(--pc-bg-2); border-block: 1px solid var(--pc-line);">
    <div class="pc-wrap">
        <div class="pc-section__head reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.formats.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.formats.title')) ?></h2>
            </div>
            <p class="pc-lead"><?= e(t('home.formats.subtitle')) ?></p>
        </div>

        <div class="pc-formats">
            <article class="pc-format reveal">
                <h3 class="pc-format__label"><?= e(t('home.formats.quick.label')) ?></h3>
                <div class="pc-format__count">
                    <b><?= e(t('home.formats.quick.count')) ?></b>
                    <span><?= e(t('home.formats.quick.unit')) ?></span>
                </div>
                <p><?= e(t('home.formats.quick.text')) ?></p>
                <hr class="pc-format__rule">
                <div class="pc-format__meta">
                    <?= icon('clock', 15) ?>
                    <?= e(t('home.formats.quick.time')) ?>
                </div>
                <a class="pc-btn pc-btn--ghost" href="<?= url('quiz.php', ['modo' => 'rapido']) ?>">
                    <span class="pc-btn__label"><?= e(t('home.formats.quick.cta')) ?></span>
                    <?= icon('arrow-right', 17) ?>
                </a>
            </article>

            <article class="pc-format pc-format--featured reveal">
                <span class="pc-format__recommended"><?= e(t('home.formats.recommended')) ?></span>
                <h3 class="pc-format__label"><?= e(t('home.formats.full.label')) ?></h3>
                <div class="pc-format__count">
                    <b><?= e(t('home.formats.full.count')) ?></b>
                    <span><?= e(t('home.formats.full.unit')) ?></span>
                </div>
                <p><?= e(t('home.formats.full.text')) ?></p>
                <hr class="pc-format__rule">
                <div class="pc-format__meta">
                    <?= icon('clock', 15) ?>
                    <?= e(t('home.formats.full.time')) ?>
                </div>
                <a class="pc-btn pc-btn--light" href="<?= url('quiz.php', ['modo' => 'completo']) ?>">
                    <span class="pc-btn__label"><?= e(t('home.formats.full.cta')) ?></span>
                    <?= icon('arrow-right', 17) ?>
                </a>
            </article>

            <article class="pc-format reveal">
                <h3 class="pc-format__label"><?= e(t('home.formats.extreme.label')) ?></h3>
                <div class="pc-format__count">
                    <b><?= e(t('home.formats.extreme.count')) ?></b>
                    <span><?= e(t('home.formats.extreme.unit')) ?></span>
                </div>
                <p><?= e(t('home.formats.extreme.text')) ?></p>
                <hr class="pc-format__rule">
                <div class="pc-format__meta">
                    <?= icon('clock', 15) ?>
                    <?= e(t('home.formats.extreme.time')) ?>
                </div>
                <a class="pc-btn pc-btn--ghost" href="<?= url('quiz.php', ['modo' => 'extremo']) ?>">
                    <span class="pc-btn__label"><?= e(t('home.formats.extreme.cta')) ?></span>
                    <?= icon('arrow-right', 17) ?>
                </a>
            </article>
        </div>

        <p class="pc-formats__note"><?= e(t('home.formats.note')) ?></p>

        <form class="pc-open-code pc-mt-4" action="<?= url('resultado.php') ?>" method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:center">
            <label class="pc-label" for="codigo-existente" style="margin:0"><?= e(t('home.open_code.label')) ?></label>
            <input class="pc-input" id="codigo-existente" name="codigo" type="text" maxlength="8" placeholder="<?= e(t('home.open_code.placeholder')) ?>" required data-code-input style="width:170px">
            <button class="pc-btn pc-btn--ghost pc-btn--sm" type="submit"><?= e(t('home.open_code.cta')) ?></button>
        </form>
    </div>
</section>

<section class="pc-section" id="comparar">
    <div class="pc-wrap">
        <div class="pc-compare reveal">
            <div>
                <span class="pc-eyebrow"><?= e(t('home.compare.eyebrow')) ?></span>
                <h2 class="pc-title"><?= e(t('home.compare.title')) ?></h2>
                <p class="pc-lead pc-mt-2"><?= e(t('home.compare.subtitle')) ?></p>
            </div>

            <div>
                <form class="pc-compare__form" action="<?= url('sinergia.php') ?>" method="get">
                    <div class="pc-field">
                        <label class="pc-label" for="codigo-a"><?= e(t('home.compare.label_a')) ?></label>
                        <input class="pc-input" id="codigo-a" name="a" type="text" maxlength="8" required data-code-input placeholder="ABCD2345">
                    </div>
                    <div class="pc-field">
                        <label class="pc-label" for="codigo-b"><?= e(t('home.compare.label_b')) ?></label>
                        <input class="pc-input" id="codigo-b" name="b" type="text" maxlength="8" required data-code-input placeholder="WXYZ6789">
                    </div>
                    <button class="pc-btn" type="submit">
                        <?= e(t('home.compare.cta')) ?>
                        <?= icon('users', 17) ?>
                    </button>
                </form>
                <p class="pc-hint"><?= e(t('home.compare.hint')) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="pc-section">
    <div class="pc-wrap pc-faq">
        <div class="reveal">
            <span class="pc-eyebrow"><?= e(t('home.faq.eyebrow')) ?></span>
            <h2 class="pc-title"><?= e(t('home.faq.title')) ?></h2>
            <p class="pc-lead pc-mt-2"><?= e(t('home.axes.subtitle')) ?></p>
        </div>

        <div class="pc-faq__list reveal">
            <?php foreach (tl('home.faq.items') as $i => $item): ?>
                <details<?= $i === 0 ? ' open' : '' ?>>
                    <summary>
                        <?= e($item['q']) ?>
                        <?= icon('chevron', 18) ?>
                    </summary>
                    <p><?= e($item['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
