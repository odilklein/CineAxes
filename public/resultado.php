<?php

declare(strict_types=1);

/** Dashboard de identidade cinéfila. */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Controllers\PerfilController;
use Core\Auth;
use Core\Database;
use Core\Radar;
use Models\Bookmark;
use Services\LikertCalculatorService;
use Services\MatcherService;

$codigoParam = $_GET['codigo'] ?? '';
$codigo = is_string($codigoParam) ? strtoupper(trim($codigoParam)) : '';

$mixParam = $_GET['mix'] ?? 'misturado';
$mix = is_string($mixParam) && in_array($mixParam, ['classicos', 'misturado', 'descobertas'], true)
    ? $mixParam
    : 'misturado';

$perfil = null;
$erro = null;
$bookmarks = [];
$authLogado = Auth::logado();

if ($codigo !== '') {
    try {
        $pdo = Database::pdo();
        $matcher = new MatcherService(
            (float) ($config['raridade']['limiar'] ?? 12.0),
            (int) ($config['raridade']['min_base'] ?? 100)
        );

        $perfil = (new PerfilController($pdo, $matcher, new LikertCalculatorService(), $config))
            ->montar($codigo, true, $mix);
        $bookmarks = Bookmark::mapa($pdo, Auth::id());
    } catch (Throwable $erroCapturado) {
        $erro = $erroCapturado;
    }
}

$narrativaSalva = $perfil['retrato_salvo']['narrativa'] ?? null;

$filmesMapa = [];
$addFilme = static function (array $filme) use (&$filmesMapa): void {
    $titulo = trim((string) ($filme['titulo'] ?? ''));

    if ($titulo === '') {
        return;
    }

    $tmdb = (int) ($filme['tmdb'] ?? $filme['tmdb_id'] ?? 0);
    $poster = $filme['poster'] ?? $filme['poster_url'] ?? null;

    if (($poster === null || $poster === '') && !empty($filme['poster_path'])) {
        $poster = tmdb_img($filme['poster_path'], 'w342');
    }

    $item = [
        'tipo' => 'catalogo',
        'serie' => (string) ($filme['tipo'] ?? 'filme') === 'serie',
        'titulo' => $titulo,
        'ano' => $filme['ano'] ?? null,
        'poster' => $poster !== '' ? $poster : null,
        'tmdb' => $tmdb,
    ];

    $chaves = [mb_strtolower($titulo)];
    $curto = preg_split('/\s*[:\-–—]\s*/u', $titulo)[0] ?? '';

    if (mb_strlen($curto) >= 5 && $curto !== $titulo) {
        $chaves[] = mb_strtolower($curto);
    }

    foreach ($chaves as $chave) {
        if (!isset($filmesMapa[$chave])) {
            $filmesMapa[$chave] = $item;
        }
    }
};

foreach ((array) ($perfil['filmes_top'] ?? []) as $filme) {
    $addFilme($filme);
}

foreach ((array) ($perfil['descobertas'] ?? []) as $filme) {
    $addFilme($filme);
}

foreach ((array) ($perfil['estante'] ?? []) as $filme) {
    $addFilme($filme);
}

foreach (['diretor', 'estudio'] as $ancora) {
    foreach ((array) ($perfil[$ancora]['obras'] ?? []) as $filme) {
        $addFilme($filme);
    }
}

foreach ((array) ($perfil['leituras'] ?? []) as $leitura) {
    foreach ((array) ($leitura['combina'] ?? []) as $filme) {
        $addFilme($filme);
    }
}

$arquetipo = $perfil['arquetipo'] ?? null;
$tituloPerfil = $arquetipo['titulo'] ?? t('resultado.neutral_title');

$paginaTitulo = $perfil !== null
    ? $tituloPerfil . ' — PersonaCut'
    : t('resultado.title') . ' — PersonaCut';

if ($perfil !== null) {
    $paginaDescricao = trim((string) ($arquetipo['descricao'] ?? ''));
    $paginaDescricao = trim((string) preg_replace('/\s+/u', ' ', strip_tags($paginaDescricao)));
    $paginaDescricao = $paginaDescricao !== ''
        ? mb_substr($paginaDescricao, 0, 155)
        : t('meta.description');
    $paginaUrl = url_absoluta(url('resultado.php', ['codigo' => $codigo]));
} else {
    $paginaDescricao = t('meta.description');
}

$vendorStyles = ['swiper/swiper-bundle.min.css'];
$scriptsExtras = ['resultado.js', 'quickview.js', 'retrato.js', 'storycard.js', 'carrossel.js', 'bookmark.js', 'filmes-links.js'];
$vendorScripts = ['swiper/swiper-bundle.min.js', 'html2canvas.min.js'];
$temGroq = trim((string) ($config['groq']['api_key'] ?? '')) !== ''
    && (bool) ($config['groq']['enabled'] ?? false);

// Retrospectiva em capítulos (padrão) ou análise completa (?modo=completo).
// A retrospectiva só entra quando o pacote dela está completo no disco; assim a
// página nunca cai num 404 de CSS/JS nem num layout sem estilo.
$modoParam = $_GET['modo'] ?? '';
// A análise é a página padrão; a retrospectiva é opt-in (?modo=retro).
$modoRetro = is_string($modoParam) && mb_strtolower(trim($modoParam)) === 'retro';
$modoCompleto = !$modoRetro;
$temRetro = is_file(__DIR__ . '/partials/resultado/retro.php')
    && is_file(dirname(__DIR__) . '/public/assets/css/retro.css')
    && is_file(dirname(__DIR__) . '/public/assets/js/retro.js');

if ($perfil !== null && $modoRetro && $temRetro) {
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

<?php if (is_array($narrativaSalva)): ?>
    <script type="application/json" data-narrativa-salva><?= json_encode($narrativaSalva, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<?php if ($filmesMapa !== []): ?>
    <script type="application/json" data-filmes-mapa><?= json_encode($filmesMapa, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>

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
    <?php if ($modoRetro && $temRetro): ?>
        <?php require __DIR__ . '/partials/resultado/retro.php'; ?>
    <?php endif; ?>
    <?php require __DIR__ . '/partials/resultado/analise.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
