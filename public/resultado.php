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

$scriptsExtras = ['resultado.js', 'quickview.js', 'retrato.js', 'storycard.js', 'trilhas.js'];
$vendorScripts = ['html2canvas.min.js'];
$temGroq = trim((string) ($config['groq']['api_key'] ?? '')) !== ''
    && (bool) ($config['groq']['enabled'] ?? false);

// Retrospectiva em capítulos (padrão) ou análise completa (?modo=completo).
// A retrospectiva só entra quando o pacote dela está completo no disco; assim a
// página nunca cai num 404 de CSS/JS nem num layout sem estilo.
$modoParam = $_GET['modo'] ?? '';
$modoCompleto = is_string($modoParam) && mb_strtolower(trim($modoParam)) === 'completo';
$temRetro = is_file(__DIR__ . '/partials/resultado/retro.php')
    && is_file(dirname(__DIR__) . '/public/assets/css/retro.css')
    && is_file(dirname(__DIR__) . '/public/assets/js/retro.js');

if ($perfil !== null && !$modoCompleto && $temRetro) {
    $scriptsExtras[] = 'retro.js';
    $estilosExtras = ['retro.css'];
    $bodyClass = 'pc-story-open';
}

$radar = '';
$extremos = [];

if ($perfil !== null) {
    $radar = Radar::render(
        array_map(static fn (array $linha): string => eixo_nome($linha['eixo']), $perfil['linhas']),
        [[
            'nome' => t('resultado.title'),
            'valores' => array_map(static fn (array $linha): float => (float) $linha['pct_dir'], $perfil['linhas']),
            'cor' => 'var(--pc-primary)',
        ]],
        array_map(static fn (array $linha): string => (string) $linha['cor'], $perfil['linhas'])
    );

    // Eixos mais distantes do neutro: usados no capítulo da assinatura.
    $extremos = $perfil['linhas'];
    usort($extremos, static function (array $a, array $b): int {
        return abs((float) $b['pct_dir'] - 50.0) <=> abs((float) $a['pct_dir'] - 50.0);
    });

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
    <?php if (!$modoCompleto && $temRetro): ?>
        <?php require __DIR__ . '/partials/resultado/retro.php'; ?>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/resultado/analise.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
