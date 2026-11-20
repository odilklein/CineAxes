<?php

declare(strict_types=1);

/**
 * Regenera a base sintética usada pelo índice de raridade.
 *
 *   docker compose exec app php bin/base_sintetica.php --recriar
 *   docker compose exec app php bin/base_sintetica.php --total=1000
 *
 * Por que existe: a base antiga era gerada com RAND(n*1000+k) no MySQL.
 * Sementes vizinhas produzem saídas correlacionadas, então os 400 perfis
 * ficavam sobre duas retas (A,B,A,B,...) em vez de uma nuvem. Isso tornava o
 * "1 em N" arbitrário.
 *
 * Aqui cada eixo é sorteado de forma independente em PHP, com distribuição
 * triangular em torno de 50 (extremos mais raros), que é o formato declarado
 * na documentação. A base continua sendo um prior, não uma medição: serve só
 * até haver perfis reais suficientes.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Models\Resultado;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$opcoes = getopt('', ['total::', 'recriar', 'ajuda']);

if (isset($opcoes['ajuda'])) {
    echo "Uso: php bin/base_sintetica.php [--total=600] [--recriar]\n";
    echo "  --total=N   quantidade de perfis (padrão 600, máximo 5000)\n";
    echo "  --recriar   apaga a base sintética anterior antes de gerar\n";
    exit(0);
}

$total = isset($opcoes['total']) && is_numeric($opcoes['total']) ? (int) $opcoes['total'] : 600;
$total = max(50, min(5000, $total));
$recriar = isset($opcoes['recriar']);

$pdo = Database::pdo();

if ($recriar) {
    $apagados = $pdo->exec(
        "DELETE FROM resultados_usuarios
          WHERE origem = 'sintetico'
            AND codigo_unico <> 'EXEMPLO1'"
    );
    printf("Base anterior removida: %d linha(s).\n", (int) $apagados);
}

/** Sorteio triangular em [0, 100] centrado em 50. */
$triangular = static function (): float {
    $a = random_int(0, 1_000_000) / 1_000_000;
    $b = random_int(0, 1_000_000) / 1_000_000;
    $valor = 50.0 + (($a + $b) / 2.0 - 0.5) * 132.0;

    return round(max(0.0, min(100.0, $valor)), 2);
};

$stmt = $pdo->prepare(
    'INSERT INTO resultados_usuarios
        (codigo_unico, idioma, modo, respostas_json, questionario_versao,
         eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
         arquetipo_id, origem, ip_hash)
     VALUES
        (:codigo, :idioma, :modo, NULL, :versao,
         :e1, :e2, :e3, :e4, :e5, :e6,
         NULL, :origem, NULL)'
);

$comVersao = Resultado::temVersao($pdo);

if (!$comVersao) {
    $stmt = $pdo->prepare(
        'INSERT INTO resultados_usuarios
            (codigo_unico, idioma, modo, respostas_json,
             eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6,
             arquetipo_id, origem, ip_hash)
         VALUES
            (:codigo, :idioma, :modo, NULL,
             :e1, :e2, :e3, :e4, :e5, :e6,
             NULL, :origem, NULL)'
    );
}

$eixos = [[], [], [], [], [], []];
$existentes = [];

foreach ($pdo->query('SELECT codigo_unico FROM resultados_usuarios') as $linha) {
    $existentes[(string) $linha['codigo_unico']] = true;
}

$pdo->beginTransaction();
$inseridos = 0;

try {
    for ($i = 1; $i <= $total; $i++) {
        $codigo = 'S' . str_pad((string) $i, 7, '0', STR_PAD_LEFT);

        // Evita colidir com um código já existente (inclusive EXEMPLO1).
        while (isset($existentes[$codigo])) {
            $i++;
            $codigo = 'S' . str_pad((string) $i, 7, '0', STR_PAD_LEFT);
        }

        $valores = [];

        foreach ($eixos as $indice => $_) {
            $valores[$indice] = $triangular();
            $eixos[$indice][] = $valores[$indice];
        }

        $parametros = [
            'codigo' => $codigo,
            'idioma' => 'pt-BR',
            'modo'   => 'completo',
            'origem' => 'sintetico',
            'e1' => $valores[0],
            'e2' => $valores[1],
            'e3' => $valores[2],
            'e4' => $valores[3],
            'e5' => $valores[4],
            'e6' => $valores[5],
        ];

        if ($comVersao) {
            $parametros['versao'] = 'sintetica-v3';
        }

        $stmt->execute($parametros);
        $inseridos++;
    }

    $pdo->commit();
} catch (Throwable $erro) {
    $pdo->rollBack();
    fwrite(STDERR, 'Falha ao gerar a base: ' . $erro->getMessage() . PHP_EOL);
    exit(1);
}

printf("Base sintética gerada: %d perfis.\n\n", $inseridos);

// Prova de que a nuvem é independente: desvio-padrão e amplitude por eixo,
// além da amplitude média dentro de cada perfil (a base antiga dava 0,3 ou 66).
printf("%-6s %8s %8s %8s\n", 'eixo', 'média', 'desvio', 'amplitude');

foreach ($eixos as $indice => $valores) {
    $media = array_sum($valores) / count($valores);
    $variancia = 0.0;

    foreach ($valores as $valor) {
        $variancia += ($valor - $media) ** 2;
    }

    printf(
        "%-6d %8.1f %8.1f %8.1f\n",
        $indice + 1,
        $media,
        sqrt($variancia / count($valores)),
        max($valores) - min($valores)
    );
}

printf("\nTotal de resultados no banco: %d (reais: %d).\n", Resultado::total($pdo), Resultado::totalReais($pdo));
