<?php

declare(strict_types=1);

/**
 * Preenche as cenas do quiz com clipes oficiais da TMDB (YouTube).
 *
 *   docker compose exec -T app php bin/midia_autofill.php --dry-run
 *   docker compose exec -T app php bin/midia_autofill.php --apply
 *
 * Opções:
 *   --dry-run          só mostra a tabela (padrão)
 *   --apply            grava as escolhas no MySQL
 *   --force            substitui perguntas que já têm vídeo
 *   --only=1,2,3       limita a algumas perguntas (id)
 *   --min-score=8      limiar de pontuação
 *   --lang=en-US       idioma consultado na TMDB
 *
 * O script nunca baixa vídeo: só grava o ID do YouTube em `perguntas.midia_*`.
 * Antes de gravar, salva um backup em storage/backups/.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Models\Pergunta;
use Services\SceneService;
use Services\TmdbService;

$opcoes = $argv ?? [];
$aplicar = in_array('--apply', $opcoes, true);
$forcar = in_array('--force', $opcoes, true);
$idioma = 'en-US';
$minimo = null;
$apenas = [];

foreach ($opcoes as $opcao) {
    if (str_starts_with($opcao, '--only=')) {
        $apenas = array_values(array_filter(array_map(
            static fn (string $id): int => (int) trim($id),
            explode(',', substr($opcao, 7))
        )));
    } elseif (str_starts_with($opcao, '--min-score=')) {
        $minimo = (float) substr($opcao, 12);
    } elseif (str_starts_with($opcao, '--lang=')) {
        $idioma = substr($opcao, 7) ?: 'en-US';
    }
}

$configMidia = require dirname(__DIR__) . '/config/midia.php';

if ($minimo !== null) {
    $configMidia['score_minimo'] = $minimo;
}

$pdo = Database::pdo();
$tmdb = new TmdbService($pdo, $config['tmdb']);

if (!$tmdb->disponivel()) {
    fwrite(STDERR, "TMDB_API_KEY ausente. Configure no .env e recrie o container.\n");
    exit(1);
}

$perguntas = Pergunta::todas($pdo);
$decisoes = [];
$linhas = [];

foreach ($perguntas as $pergunta) {
    $id = (int) $pergunta['id'];

    if ($apenas !== [] && !in_array($id, $apenas, true)) {
        continue;
    }

    $perfil = SceneService::perfil($pergunta, $configMidia);
    $tmdbId = (int) ($perfil['tmdb_id'] ?? 0);

    if ($tmdbId <= 0) {
        $linhas[] = [$id, $perfil['titulo'] ?: '—', '—', '—', 'sem tmdb_id'];
        continue;
    }

    if (!$forcar && ($pergunta['midia_tipo'] ?? null) === 'video_yt') {
        $linhas[] = [$id, $perfil['titulo'], '—', '—', 'já tem vídeo (use --force)'];
        continue;
    }

    $candidatos = SceneService::candidatos($tmdb->videos($tmdbId, $idioma));
    $escolha = SceneService::escolher($candidatos, $perfil, $configMidia);

    if ($escolha === null) {
        $avaliados = SceneService::avaliarCandidatos($candidatos, $perfil, $configMidia);
        $melhor = $avaliados[0] ?? null;
        $nota = $melhor === null
            ? 'mantém imagem (nenhum Clip válido)'
            : sprintf('mantém imagem (melhor score %.1f)', (float) $melhor['score']);

        $linhas[] = [$id, $perfil['titulo'], '—', '—', $nota];
        continue;
    }

    $meta = SceneService::meta($escolha, $perfil, $configMidia);

    $decisoes[] = [
        'id'    => $id,
        'tipo'  => 'video_yt',
        'url'   => (string) $escolha['key'],
        'meta'  => $meta,
        'score' => (float) $escolha['score'],
        'nome'  => (string) $escolha['nome'],
    ];

    $linhas[] = [
        $id,
        $perfil['titulo'],
        $escolha['nome'],
        $escolha['key'],
        sprintf('video_yt · score %.1f', (float) $escolha['score']),
    ];
}

$larguras = [3, 28, 48, 13, 30];

foreach ($linhas as $linha) {
    foreach (array_values($linha) as $indice => $valor) {
        $larguras[$indice] = max($larguras[$indice] ?? 0, mb_strlen((string) $valor));
    }
}

$separador = '+' . implode('+', array_map(static fn (int $w): string => str_repeat('-', $w + 2), $larguras)) . '+';
$formatar = static function (array $linha) use ($larguras): string {
    $celulas = [];

    foreach (array_values($linha) as $indice => $valor) {
        $texto = (string) $valor;
        $celulas[] = ' ' . $texto . str_repeat(' ', max(0, $larguras[$indice] - mb_strlen($texto))) . ' ';
    }

    return '|' . implode('|', $celulas) . '|';
};

echo "\n  PersonaCut · cenas automáticas da TMDB\n";
echo '  ' . str_repeat('─', 70) . "\n";
echo '  Modo: ' . ($aplicar ? 'APLICAR' : 'DRY-RUN') . " · idioma: {$idioma} · mínimo: "
    . (float) $configMidia['score_minimo'] . "\n\n";
echo $separador . "\n";
echo $formatar(['ID', 'Filme', 'Clipe escolhido', 'YouTube key', 'Decisão']) . "\n";
echo $separador . "\n";

foreach ($linhas as $linha) {
    echo $formatar($linha) . "\n";
}

echo $separador . "\n";
printf("  %d pergunta(s) analisada(s) · %d clipe(s) selecionado(s)\n\n", count($linhas), count($decisoes));

if (!$aplicar) {
    echo "  Nada foi gravado. Rode com --apply para aplicar.\n\n";
    exit(0);
}

if ($decisoes === []) {
    echo "  Nenhuma alteração para aplicar.\n\n";
    exit(0);
}

$diretorioBackup = dirname(__DIR__) . '/storage/backups';

if (!is_dir($diretorioBackup) && !mkdir($diretorioBackup, 0775, true) && !is_dir($diretorioBackup)) {
    fwrite(STDERR, "Não foi possível criar {$diretorioBackup}.\n");
    exit(1);
}

$arquivoBackup = $diretorioBackup . '/midia_backup_' . date('Ymd-His') . '.json';
$atuais = $pdo->query(
    'SELECT id, texto_key, midia_tipo, midia_url_ou_id, midia_meta FROM perguntas ORDER BY id'
)->fetchAll();

file_put_contents(
    $arquivoBackup,
    json_encode($atuais, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

$stmt = $pdo->prepare(
    'UPDATE perguntas
        SET midia_tipo = :tipo, midia_url_ou_id = :url, midia_meta = :meta
      WHERE id = :id'
);

$pdo->beginTransaction();

try {
    foreach ($decisoes as $decisao) {
        $stmt->execute([
            'tipo' => $decisao['tipo'],
            'url'  => $decisao['url'],
            'meta' => json_encode($decisao['meta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'id'   => $decisao['id'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $erro) {
    $pdo->rollBack();
    fwrite(STDERR, 'Falha ao aplicar: ' . $erro->getMessage() . "\n");
    exit(1);
}

echo "  Aplicado. Backup: storage/backups/" . basename($arquivoBackup) . "\n\n";
