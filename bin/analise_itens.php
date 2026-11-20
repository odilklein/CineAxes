<?php

declare(strict_types=1);

/**
 * Análise psicométrica dos itens do questionário.
 *
 *   docker compose exec app php bin/analise_itens.php
 *   docker compose exec app php bin/analise_itens.php --minimo=10
 *
 * Para cada eixo calcula:
 *   - alfa de Cronbach (consistência interna)
 *   - correlação item-total corrigida (discriminação do item)
 *   - média e desvio do item já orientado para o polo direito
 *
 * Itens com discriminação baixa (< 0,10) ou negativa não medem o eixo: são
 * candidatos a reescrita. Com poucos perfis o resultado é indicativo, não
 * conclusivo — o script informa o n de cada item.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Models\Pergunta;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$opcoes = getopt('', ['minimo::', 'ajuda']);

if (isset($opcoes['ajuda'])) {
    echo "Uso: php bin/analise_itens.php [--minimo=5]\n";
    echo "  --minimo=N  respostas mínimas para o item entrar no relatório (padrão 5)\n";
    exit(0);
}

$minimo = isset($opcoes['minimo']) && is_numeric($opcoes['minimo']) ? max(2, (int) $opcoes['minimo']) : 5;

$pdo = Database::pdo();
$mapa = Pergunta::mapaParaCalculo(Pergunta::todas($pdo));

$perfis = $pdo->query(
    "SELECT codigo_unico, respostas_json
       FROM resultados_usuarios
      WHERE origem = 'real'
        AND respostas_json IS NOT NULL
      ORDER BY created_at"
)->fetchAll();

if (count($perfis) < 3) {
    fwrite(STDERR, "Poucos perfis reais com respostas (" . count($perfis) . "). A análise precisa de pelo menos 3.\n");
    exit(1);
}

// matriz pergunta_id => [indice_do_perfil => resposta já orientada]
$dados = [];
$porEixo = [];

foreach ($perfis as $indice => $perfil) {
    $respostas = json_decode((string) $perfil['respostas_json'], true);

    if (!is_array($respostas)) {
        continue;
    }

    foreach ($respostas as $idPergunta => $valor) {
        $meta = $mapa[(int) $idPergunta] ?? null;

        if ($meta === null || !is_numeric($valor)) {
            continue;
        }

        $dados[(int) $idPergunta][$indice] = (int) $valor * (int) $meta['direcao'];
        $porEixo[$meta['eixo_id']][(int) $idPergunta] = true;
    }
}

$textos = [];

foreach (Pergunta::todas($pdo) as $pergunta) {
    $textos[(int) $pergunta['id']] = (string) $pergunta['texto'];
}

$medias = static function (array $valores): array {
    $n = count($valores);
    $media = array_sum($valores) / max(1, $n);
    $variancia = 0.0;

    foreach ($valores as $valor) {
        $variancia += ($valor - $media) ** 2;
    }

    return [$media, $n > 1 ? sqrt($variancia / ($n - 1)) : 0.0];
};

$correlacao = static function (array $x, array $y): ?float {
    $pares = [];
    $n = min(count($x), count($y));

    for ($i = 0; $i < $n; $i++) {
        if (isset($x[$i], $y[$i])) {
            $pares[] = [(float) $x[$i], (float) $y[$i]];
        }
    }

    if (count($pares) < 3) {
        return null;
    }

    $mediaX = array_sum(array_column($pares, 0)) / count($pares);
    $mediaY = array_sum(array_column($pares, 1)) / count($pares);
    $cov = 0.0;
    $varX = 0.0;
    $varY = 0.0;

    foreach ($pares as [$a, $b]) {
        $cov += ($a - $mediaX) * ($b - $mediaY);
        $varX += ($a - $mediaX) ** 2;
        $varY += ($b - $mediaY) ** 2;
    }

    if ($varX <= 0.0 || $varY <= 0.0) {
        return null;
    }

    return $cov / sqrt($varX * $varY);
};

echo "\n  PersonaCut · análise de itens\n";
echo str_repeat('─', 78) . "\n";
printf("  %d perfis reais com respostas\n", count($perfis));

$distribuicao = [];

foreach ($perfis as $perfil) {
    $quantidade = is_string($perfil['respostas_json'])
        ? count(json_decode((string) $perfil['respostas_json'], true) ?: [])
        : 0;
    $distribuicao[$quantidade] = ($distribuicao[$quantidade] ?? 0) + 1;
}

ksort($distribuicao);
echo "  respostas por perfil: ";

foreach ($distribuicao as $quantidade => $total) {
    printf("%d (%d) ", $quantidade, $total);
}

echo "\n\n";

foreach ($porEixo as $eixoId => $itens) {
    $ids = array_keys($itens);
    echo "  EIXO {$eixoId} · " . count($ids) . " item(ns) respondido(s)\n";

    // Alpha de Cronbach com deleção por lista: só perfis que responderam todos.
    $completos = [];
    $indices = array_keys($perfis);

    foreach ($indices as $indice) {
        $ok = true;

        foreach ($ids as $id) {
            if (!isset($dados[$id][$indice])) {
                $ok = false;
                break;
            }
        }

        if ($ok) {
            $completos[] = $indice;
        }
    }

    if (count($completos) >= 3 && count($ids) >= 2) {
        $variancias = 0.0;
        $totais = [];

        foreach ($completos as $indice) {
            $soma = 0;

            foreach ($ids as $id) {
                $soma += $dados[$id][$indice];
            }

            $totais[] = $soma;
        }

        foreach ($ids as $id) {
            [, $dp] = $medias(array_map(static fn (int $i): int => $dados[$id][$i], $completos));
            $variancias += $dp ** 2;
        }

        [, $dpTotal] = $medias($totais);
        $k = count($ids);
        $alpha = $dpTotal > 0 ? ($k / ($k - 1)) * (1 - $variancias / ($dpTotal ** 2)) : null;

        printf(
            "    alfa de Cronbach: %s (%d perfis completos)\n",
            $alpha === null ? '—' : number_format($alpha, 2, ',', '.'),
            count($completos)
        );
    } else {
        echo "    alfa de Cronbach: dados insuficientes (faltam perfis com todos os itens)\n";
    }

    foreach ($ids as $id) {
        $valores = $dados[$id];
        [$media, $dp] = $medias(array_values($valores));

        $totalSemItem = [];

        foreach ($valores as $indice => $valor) {
            $soma = 0;

            foreach ($ids as $outro) {
                if ($outro !== $id && isset($dados[$outro][$indice])) {
                    $soma += $dados[$outro][$indice];
                }
            }

            $totalSemItem[$indice] = $soma;
        }

        $r = $correlacao($valores, $totalSemItem);
        $n = count($valores);

        if ($n < $minimo) {
            continue;
        }

        $alerta = $r !== null && $r < 0.10 ? '  <-- reescrever' : '';
        printf(
            "    item %-4d n=%-3d média=%+.2f dp=%.2f r=%s%s\n    %s\n",
            $id,
            $n,
            $media,
            $dp,
            $r === null ? '—' : number_format($r, 2, ',', '.'),
            $alerta,
            mb_substr($textos[$id] ?? '', 0, 74)
        );
    }

    echo "\n";
}

echo str_repeat('─', 78) . "\n";
echo "  Leitura: r < 0,10 indica item que não acompanha o resto do eixo.\n";
echo "  Alfa abaixo de 0,60 sugere eixo com poucos itens ou itens heterogêneos.\n\n";
