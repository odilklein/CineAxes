<?php

declare(strict_types=1);

/**
 * Ancora o vetor das entidades no catálogo.
 *
 *   docker compose exec app php bin/calibrar_entidades.php
 *   docker compose exec app php bin/calibrar_entidades.php --aplicar --peso-catalogo=0.5
 *
 * Hoje os 52 vetores de `entidades_match` foram atribuídos à mão. Este script
 * calcula um centroide a partir das obras do catálogo:
 *
 *   - personagens: a obra de origem (tmdb_id, ou título + ano)
 *   - diretores/escolas: a média das obras de `entidades_obras`
 *
 * Sem `--aplicar` ele só relata a diferença entre o vetor editorial e o
 * centroide empírico. Com `--aplicar`, grava a mistura
 * `peso_catalogo * centroide + (1 - peso_catalogo) * editorial` e marca
 * `vetor_origem = 'misto'`.
 *
 * Isto é o primeiro passo da calibração, não o fim: o ideal é validar a
 * mistura com julgamentos pareados antes de promover para 'catalogo'.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Core\Vetor;
use Models\CatalogoObra;
use Models\EntidadeMatch;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$opcoes = getopt('', ['aplicar', 'peso-catalogo::', 'limite::', 'ajuda']);

if (isset($opcoes['ajuda'])) {
    echo "Uso: php bin/calibrar_entidades.php [--aplicar] [--peso-catalogo=0.5] [--limite=10]\n";
    echo "  --aplicar          grava a mistura no banco (sem isso, só relata)\n";
    echo "  --peso-catalogo=N  peso do centroide (0 a 1, padrão 0.5)\n";
    echo "  --limite=N         quantas divergências listar (padrão 10)\n";
    exit(0);
}

$aplicar = isset($opcoes['aplicar']);
$peso = isset($opcoes['peso-catalogo']) && is_numeric($opcoes['peso-catalogo'])
    ? max(0.0, min(1.0, (float) $opcoes['peso-catalogo']))
    : 0.5;
$limite = isset($opcoes['limite']) && is_numeric($opcoes['limite']) ? max(1, (int) $opcoes['limite']) : 10;

$pdo = Database::pdo();
$obras = CatalogoObra::todos($pdo);
$entidades = EntidadeMatch::todas($pdo);

$porTmdb = [];
$porTitulo = [];

foreach ($obras as $obra) {
    if (!Vetor::valido($obra['vetor'] ?? [])) {
        continue;
    }

    $porTmdb[(int) $obra['tmdb_id']][] = $obra;

    $chave = mb_strtolower(trim((string) $obra['titulo'])) . '|' . (int) ($obra['ano'] ?? 0);
    $porTitulo[$chave] = $obra;

    $original = mb_strtolower(trim((string) ($obra['titulo_original'] ?? '')));

    if ($original !== '') {
        $porTitulo[$original . '|' . (int) ($obra['ano'] ?? 0)] = $obra;
    }
}

// Obras essenciais de diretores e escolas (tmdb_id das obras).
$obrasEntidade = [];

foreach ($pdo->query('SELECT entidade_id, tmdb_id FROM entidades_obras WHERE tmdb_id IS NOT NULL') as $linha) {
    $obrasEntidade[(int) $linha['entidade_id']][] = (int) $linha['tmdb_id'];
}

/** @param list<array<string, mixed>> $obras @return list<float>|null */
$centroide = static function (array $obras, string $tipo): ?array {
    if ($obras === []) {
        return null;
    }

    $soma = array_fill(0, Vetor::EIXOS, 0.0);
    $pesoTotal = 0.0;

    foreach ($obras as $obra) {
        // A obra de origem de um personagem é um único filme; para diretores e
        // escolas a média é ponderada pela confiança do vetor da obra.
        $peso = $tipo === 'personagem' ? 1.0 : max(0.25, (float) ($obra['confianca'] ?? 50) / 100);

        foreach ($obra['vetor'] as $indice => $valor) {
            $soma[$indice] += (float) $valor * $peso;
        }

        $pesoTotal += $peso;
    }

    return array_map(static fn (float $valor): float => round($valor / max(0.0001, $pesoTotal), 2), $soma);
};

$relatorio = [];
$cobertos = ['personagem' => 0, 'diretor' => 0, 'estudio' => 0];
$totais = ['personagem' => 0, 'diretor' => 0, 'estudio' => 0];

foreach ($entidades as $entidade) {
    $tipo = (string) $entidade['tipo'];
    $totais[$tipo] = ($totais[$tipo] ?? 0) + 1;

    $candidatas = [];

    if (!empty($entidade['tmdb_id']) && isset($porTmdb[(int) $entidade['tmdb_id']])) {
        $candidatas = $porTmdb[(int) $entidade['tmdb_id']];
    } else {
        $chave = mb_strtolower(trim((string) $entidade['obra_origem'])) . '|' . (int) ($entidade['ano'] ?? 0);

        if (isset($porTitulo[$chave])) {
            $candidatas[] = $porTitulo[$chave];
        }
    }

    if ($tipo !== 'personagem' && isset($obrasEntidade[(int) $entidade['id']])) {
        foreach ($obrasEntidade[(int) $entidade['id']] as $tmdbId) {
            foreach ($porTmdb[$tmdbId] ?? [] as $obra) {
                $candidatas[] = $obra;
            }
        }
    }

    $vetor = $centroide($candidatas, $tipo);

    if ($vetor === null) {
        continue;
    }

    $cobertos[$tipo] = ($cobertos[$tipo] ?? 0) + 1;

    $editorial = $entidade['vetor6'] ?? \Core\Vetor::projetar6($entidade['vetor']);
    $diferenca = 0.0;

    foreach ($editorial as $indice => $valor) {
        $diferenca += abs((float) $valor - (float) $vetor[$indice]);
    }

    $relatorio[] = [
        'id' => (int) $entidade['id'],
        'nome' => (string) $entidade['nome'],
        'tipo' => $tipo,
        'obras' => count($candidatas),
        'editorial' => $editorial,
        'catalogo' => $vetor,
        'diferenca' => round($diferenca / Vetor::EIXOS, 1),
    ];
}

echo "\n  PersonaCut · calibração de entidades pelo catálogo\n";
echo str_repeat('─', 84) . "\n";
printf("  Cobertura: personagens %d/%d · diretores %d/%d · escolas %d/%d\n\n",
    $cobertos['personagem'], $totais['personagem'],
    $cobertos['diretor'], $totais['diretor'],
    $cobertos['estudio'], $totais['estudio']);

usort($relatorio, static fn (array $a, array $b): int => $b['diferenca'] <=> $a['diferenca']);

echo "  Maiores divergências (média absoluta por eixo):\n";

foreach (array_slice($relatorio, 0, $limite) as $linha) {
    printf(
        "    %-34s %-10s obras=%d  Δ=%.1f\n      editorial: %s\n      catálogo : %s\n",
        mb_substr($linha['nome'], 0, 34),
        $linha['tipo'],
        $linha['obras'],
        $linha['diferenca'],
        implode(', ', array_map('intval', $linha['editorial'])),
        implode(', ', array_map('intval', $linha['catalogo']))
    );
}

$media = $relatorio !== []
    ? round(array_sum(array_column($relatorio, 'diferenca')) / count($relatorio), 1)
    : 0.0;

printf("\n  Divergência média: %.1f pontos por eixo em %d entidades.\n", $media, count($relatorio));

if (!$aplicar) {
    echo "  Nada foi gravado. Use --aplicar para misturar (peso catálogo = {$peso}).\n\n";
    exit(0);
}

fwrite(STDERR, "No modelo v2, diretores/escolas são calculados da filmografia em tempo de execução.\n");
fwrite(STDERR, "--aplicar foi desativado para não gravar um vetor de 6 eixos nas colunas legadas de 8.\n");
exit(0);

$atualizar = $pdo->prepare(
    'UPDATE entidades_match
        SET eixo_1 = :e1, eixo_2 = :e2, eixo_3 = :e3, eixo_4 = :e4,
            eixo_5 = :e5, eixo_6 = :e6, eixo_7 = :e7, eixo_8 = :e8,
            vetor_origem = :origem
      WHERE id = :id'
);

$pdo->beginTransaction();
$gravadas = 0;

try {
    foreach ($relatorio as $linha) {
        $mistura = [];

        foreach ($linha['editorial'] as $indice => $valor) {
            $mistura[] = (int) round(
                $peso * (float) $linha['catalogo'][$indice] + (1.0 - $peso) * (float) $valor
            );
        }

        $atualizar->execute([
            'e1' => $mistura[0],
            'e2' => $mistura[1],
            'e3' => $mistura[2],
            'e4' => $mistura[3],
            'e5' => $mistura[4],
            'e6' => $mistura[5],
            'e7' => $mistura[6],
            'e8' => $mistura[7],
            'origem' => 'misto',
            'id' => $linha['id'],
        ]);
        $gravadas++;
    }

    $pdo->commit();
} catch (Throwable $erro) {
    $pdo->rollBack();
    fwrite(STDERR, 'Falha ao gravar: ' . $erro->getMessage() . PHP_EOL);
    exit(1);
}

printf("  %d entidades atualizadas com mistura %.0f%% catálogo / %.0f%% editorial.\n\n",
    $gravadas, $peso * 100, (1 - $peso) * 100);
