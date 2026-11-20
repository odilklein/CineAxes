<?php

declare(strict_types=1);

/**
 * Abertura do documento.
 *
 * Variáveis opcionais: $paginaTitulo, $paginaDescricao, $bodyClass, $ogImagem,
 * $ogImagemAlt, $ogTipo, $paginaUrl, $estilosExtras (list<string>) — caminhos
 * extras em assets/css.
 */

$paginaTitulo    = $paginaTitulo ?? t('meta.title');
$paginaDescricao = $paginaDescricao ?? t('meta.description');
$bodyClass       = $bodyClass ?? '';
$estilosExtras   = $estilosExtras ?? [];
$vendorStyles    = $vendorStyles ?? [];
$ogTipo          = $ogTipo ?? 'website';
$ogImagem        = $ogImagem ?? '/assets/img/og-personacut.jpg';
$ogImagemAlt     = $ogImagemAlt ?? 'Cartaz do PersonaCut com um card de perfil cinéfilo: “Que tipo de cinema mora em você?”';
$ogImagemAbsoluta = preg_match('#^https?://#i', $ogImagem) === 1
    ? $ogImagem
    : url_absoluta($ogImagem);

$paginaUrl = $paginaUrl ?? url_absoluta(
    (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/')
);
?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($paginaTitulo) ?></title>
    <meta name="description" content="<?= e($paginaDescricao) ?>">
    <meta name="theme-color" content="#53201c">
    <meta name="csrf-token" content="<?= e(\Core\Csrf::token()) ?>">
    <link rel="canonical" href="<?= e($paginaUrl) ?>">

    <meta property="og:type" content="<?= e($ogTipo) ?>">
    <meta property="og:site_name" content="PersonaCut">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="<?= e($paginaUrl) ?>">
    <meta property="og:title" content="<?= e($paginaTitulo) ?>">
    <meta property="og:description" content="<?= e($paginaDescricao) ?>">
    <meta property="og:image" content="<?= e($ogImagemAbsoluta) ?>">
    <meta property="og:image:secure_url" content="<?= e($ogImagemAbsoluta) ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= e($ogImagemAlt) ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($paginaTitulo) ?>">
    <meta name="twitter:description" content="<?= e($paginaDescricao) ?>">
    <meta name="twitter:image" content="<?= e($ogImagemAbsoluta) ?>">
    <meta name="twitter:image:alt" content="<?= e($ogImagemAlt) ?>">
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
