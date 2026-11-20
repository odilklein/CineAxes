<?php

declare(strict_types=1);

/** Lista pessoal: "quero ver" e "já vi". */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Auth;
use Core\Database;
use Models\Bookmark;

// Lista pessoal desligada por enquanto (FEATURE_WATCHLIST=false).
if (!($config['features']['watchlist'] ?? false)) {
    header('Location: ' . url('/'), true, 302);
    exit;
}

if (!Auth::logado()) {
    header('Location: ' . url('entrar.php', ['next' => '/minha-lista.php']));
    exit;
}

$pdo = Database::pdo();
$usuarioId = (int) Auth::id();

$statusParam = is_string($_GET['status'] ?? null) ? (string) $_GET['status'] : 'quero';
$status = in_array($statusParam, ['quero', 'visto'], true) ? $statusParam : 'quero';

$itens = Bookmark::listar($pdo, $usuarioId, $status);
$contagem = Bookmark::contar($pdo, $usuarioId);
$mapa = Bookmark::mapa($pdo, $usuarioId);
$authLogado = true;
$bookmarks = $mapa;

$paginaTitulo = t('auth.list_title') . ' — PersonaCut';
$scriptsExtras = ['bookmark.js'];
$navSimples = true;

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<section class="pc-section">
    <div class="pc-wrap">
        <span class="pc-eyebrow"><?= e(t('auth.list_eyebrow')) ?></span>
        <h1 class="pc-title"><?= e(t('auth.list_title')) ?></h1>
        <p class="pc-lead pc-mt-2"><?= e(t('auth.list_lead')) ?></p>

        <nav class="pc-auth__tabs" aria-label="<?= e(t('auth.list_title')) ?>">
            <a class="pc-auth__tab<?= $status === 'quero' ? ' is-active' : '' ?>"
               data-status="quero"
               href="<?= e(url('minha-lista.php', ['status' => 'quero'])) ?>">
                <?= e(t('auth.list_want')) ?>
                <b><?= (int) $contagem['quero'] ?></b>
            </a>
            <a class="pc-auth__tab<?= $status === 'visto' ? ' is-active' : '' ?>"
               data-status="visto"
               href="<?= e(url('minha-lista.php', ['status' => 'visto'])) ?>">
                <?= e(t('auth.list_seen')) ?>
                <b><?= (int) $contagem['visto'] ?></b>
            </a>
        </nav>

        <?php if ($itens === []): ?>
            <div class="pc-empty pc-mt-3">
                <?= e($status === 'quero' ? t('auth.list_empty_want') : t('auth.list_empty_seen')) ?>
            </div>
            <p class="pc-mt-2">
                <a class="pc-link-arrow" href="<?= e(url('quiz.php')) ?>">
                    <?= e(t('auth.list_cta')) ?> <?= icon('arrow-right', 15) ?>
                </a>
            </p>
        <?php else: ?>
            <div class="pc-film-grid pc-mt-3" data-lista-status="<?= e($status) ?>">
                <?php foreach ($itens as $item): ?>
                    <?php
                    $poster = tmdb_img($item['poster_path'] ?? null, 'w342');
                    $quick = [
                        'tipo' => 'catalogo',
                        'serie' => (string) $item['tipo'] === 'serie',
                        'titulo' => (string) $item['titulo'],
                        'ano' => $item['ano'] !== null ? (int) $item['ano'] : null,
                        'poster' => $poster !== '' ? $poster : null,
                        'tmdb' => (int) $item['tmdb_id'],
                    ];
                    $estado = $mapa[(int) $item['tmdb_id']] ?? null;
                    ?>
                    <article class="pc-film" data-bookmark-card data-tmdb="<?= (int) $item['tmdb_id'] ?>">
                        <div class="pc-film__poster" data-quickview="<?= json_attr($quick) ?>" tabindex="0" role="button"
                             aria-label="<?= e(t('auth.open_sheet', ['titulo' => (string) $item['titulo']])) ?>">
                            <?php if ($poster !== ''): ?>
                                <img src="<?= e($poster) ?>" alt="Pôster de <?= e((string) $item['titulo']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <span class="pc-film__fallback"><?= e((string) $item['titulo']) ?></span>
                            <?php endif; ?>
                            <?php
                            $bmTmdb = (int) $item['tmdb_id'];
                            $bmTipo = (string) $item['tipo'];
                            $bmTitulo = (string) $item['titulo'];
                            $bmAno = $item['ano'] !== null ? (int) $item['ano'] : null;
                            $bmPoster = (string) ($item['poster_path'] ?? '');
                            $bmEstado = $estado;
                            $bmAuth = true;
                            $bmInline = false;
                            require __DIR__ . '/partials/bookmark-actions.php';
                            ?>
                        </div>

                        <div class="pc-film__body">
                            <b><?= e((string) $item['titulo']) ?></b>
                            <small><?= $item['ano'] !== null ? (int) $item['ano'] . ' · ' : '' ?><?= e((string) $item['tipo']) ?></small>

                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
