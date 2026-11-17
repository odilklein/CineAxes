<?php
require __DIR__ . '/include/config.php';
require __DIR__ . '/include/conexao.php';

// Consulta simples só para validar ambiente + banco (o cartaz completo vem na Semana 2/3)
$filmes = $pdo->query(
    "SELECT f.id, f.titulo, f.sinopse, f.duracao_min, f.classificacao, g.nome AS genero
       FROM filmes f
       LEFT JOIN generos g ON g.id = f.genero_id
      WHERE f.status = 'em_cartaz'
      ORDER BY f.titulo"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($nome_cinema) ?> — Em cartaz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">🎬 <?= htmlspecialchars($nome_cinema) ?></a>
        <span class="badge text-bg-warning">em desenvolvimento</span>
    </div>
</nav>

<main class="container pb-5">
    <div class="p-4 p-md-5 mb-4 rounded-3 bg-dark text-white">
        <h1 class="display-6 fw-bold"><?= saudar() ?>! 🍿</h1>
        <p class="lead mb-0">Confira os filmes em cartaz no <?= htmlspecialchars($nome_cinema) ?>.</p>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h4 mb-0">Em cartaz</h2>
        <span class="text-secondary small"><?= count($filmes) ?> filme(s)</span>
    </div>

    <div class="row g-4">
        <?php foreach ($filmes as $filme): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="ratio ratio-2x3 bg-secondary-subtle d-flex align-items-center justify-content-center">
                        <span class="display-4">🎞️</span>
                    </div>
                    <div class="card-body">
                        <h3 class="h5 card-title mb-1"><?= htmlspecialchars($filme['titulo']) ?></h3>
                        <p class="text-secondary small mb-2">
                            <?= htmlspecialchars($filme['genero'] ?? 'Sem gênero') ?>
                            · <?= (int) $filme['duracao_min'] ?> min
                            · <span class="badge text-bg-secondary"><?= htmlspecialchars($filme['classificacao']) ?></span>
                        </p>
                        <p class="card-text small"><?= htmlspecialchars($filme['sinopse'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($filmes)): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">Nenhum filme em cartaz. Rode o seed do banco (`sql/002_seed.sql`).</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="border-top py-4 mt-auto">
    <div class="container text-secondary small">
        <?= htmlspecialchars($nome_cinema) ?> · <?= htmlspecialchars($endereco) ?> · <?= htmlspecialchars($contato) ?>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
