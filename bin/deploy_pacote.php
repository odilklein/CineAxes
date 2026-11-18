<?php

declare(strict_types=1);

/**
 * Monta o pacote SQL do InfinityFree.
 *
 *   docker compose exec app php bin/deploy_pacote.php
 *
 * Gera dois arquivos em deploy/infinityfree/:
 *   - personacut.sql  → schema + seed + enriquecimento + rótulos + catálogo (tabelas)
 *   - catalogo.sql    → snapshot do catálogo (gerado por bin/catalogo_export.php)
 *
 * A separação existe por causa do limite de 10 MB por arquivo do InfinityFree:
 * se o snapshot crescer, ele pode ser dividido sem mexer no schema.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$raiz = dirname(__DIR__);
$destino = $raiz . '/deploy/infinityfree';

$base = [
    'sql/001_schema.sql',
    'sql/002_seed.sql',
    'sql/003_enriquecimento.sql',
    'sql/004_cenas_video.sql',
    'sql/005_rotulos.sql',
    'sql/009_catalogo.sql',
];

$cabecalho = static function (string $titulo, array $arquivos): string {
    $linhas = [];
    $linhas[] = '-- =============================================================================';
    $linhas[] = '-- PersonaCut — ' . $titulo;
    $linhas[] = '-- Gerado em ' . date('Y-m-d H:i:s') . ' por bin/deploy_pacote.php';
    $linhas[] = '-- Fontes: ' . implode(', ', $arquivos);
    $linhas[] = '-- Importe primeiro personacut.sql e depois catalogo.sql.';
    $linhas[] = '-- =============================================================================';
    $linhas[] = '';

    return implode("\n", $linhas);
};

$juntar = static function (array $arquivos) use ($raiz): string {
    $partes = [];

    foreach ($arquivos as $arquivo) {
        $caminho = $raiz . '/' . $arquivo;

        if (!is_file($caminho)) {
            fwrite(STDERR, "Arquivo ausente: {$arquivo}\n");
            exit(1);
        }

        $partes[] = "-- >>> {$arquivo}\n" . rtrim((string) file_get_contents($caminho)) . "\n";
    }

    return implode("\n", $partes);
};

$saidaBase = $destino . '/personacut.sql';

if (file_put_contents($saidaBase, $cabecalho('banco completo (sem catálogo)', $base) . $juntar($base)) === false) {
    fwrite(STDERR, "Não foi possível gravar {$saidaBase}\n");
    exit(1);
}

printf("Base gerada: %s (%.2f MB)\n", $saidaBase, filesize($saidaBase) / 1048576);

$catalogo = 'sql/010_catalogo_seed.sql';
$saidaCatalogo = $destino . '/catalogo.sql';
$caminhoCatalogo = $raiz . '/' . $catalogo;

if (!is_file($caminhoCatalogo)) {
    fwrite(STDERR, "Snapshot do catálogo ausente. Rode bin/catalogo_export.php antes.\n");
    exit(1);
}

$conteudo = $cabecalho('catálogo audiovisual', [$catalogo])
    . rtrim((string) file_get_contents($caminhoCatalogo)) . "\n";

if (file_put_contents($saidaCatalogo, $conteudo) === false) {
    fwrite(STDERR, "Não foi possível gravar {$saidaCatalogo}\n");
    exit(1);
}

$tamanho = filesize($saidaCatalogo) / 1048576;

printf("Catálogo gerado: %s (%.2f MB)\n", $saidaCatalogo, $tamanho);

if ($tamanho > 9.5) {
    fwrite(STDERR, "Atenção: acima do limite de 10 MB do InfinityFree. Divida catalogo.sql em partes.\n");
    exit(2);
}
