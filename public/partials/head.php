<?php

declare(strict_types=1);

/**
 * Abertura do documento.
 *
 * Variáveis opcionais: $paginaTitulo, $paginaDescricao, $bodyClass, $ogImagem,
 * $estilosExtras (list<string>) — caminhos extras em assets/css.
 */

$paginaTitulo    = $paginaTitulo ?? t('meta.title');
$paginaDescricao = $paginaDescricao ?? t('meta.description');
$bodyClass       = $bodyClass ?? '';
$estilosExtras   = $estilosExtras ?? [];
$vendorStyles    = $vendorStyles ?? [];
?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($paginaTitulo) ?></title>
    <meta name="description" content="<?= e($paginaDescricao) ?>">
    <meta name="theme-color" content="#0c3b2e">
    <meta name="csrf-token" content="<?= e(\Core\Csrf::token()) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($paginaTitulo) ?>">
    <meta property="og:description" content="<?= e($paginaDescricao) ?>">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preload" href="<?= asset('fonts/public-sans-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>

    <script src="<?= asset('js/theme.js') ?>"></script>

    <link rel="stylesheet" href="<?= asset('vendor/vuexy/core.css') ?>">
    <?php foreach ($vendorStyles as $vendorStyle): ?>
    <link rel="stylesheet" href="<?= asset('vendor/' . $vendorStyle) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= asset('css/device-iphone.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/personacut.css') ?>">
    <?php foreach ($estilosExtras as $estilo): ?>
    <link rel="stylesheet" href="<?= asset('css/' . $estilo) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="pc-skip" href="#conteudo"><?= e(t('ui.skip')) ?></a>
