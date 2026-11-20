<?php

declare(strict_types=1);

/**
 * Catálogo audiovisual — ingestão via TMDB.
 *
 *   docker compose exec app php bin/catalogo_sync.php --piloto
 *   docker compose exec app php bin/catalogo_sync.php --trilha=coreia-doramas --limite=40
 *   docker compose exec app php bin/catalogo_sync.php
 *   docker compose exec app php bin/catalogo_sync.php --recalcular
 *   docker compose exec app php bin/catalogo_sync.php --force --sem-provedores
 *
 * O comando roda SEMPRE fora do request (na máquina de desenvolvimento) e grava
 * no MySQL. O site publicado lê apenas o banco: nenhuma chamada de rede para
 * montar catálogo, cards ou ranking.
 *
 * Fluxo:
 *   1. discover por trilha (config/recomendacao.php) e por plataforma de nicho;
 *   2. detalhe de cada título com append_to_response=watch/providers;
 *   3. vetor de 8 eixos + confiança calculados por RecomendacaoService;
 *   4. gêneros, países e disponibilidade normalizados em tabelas próprias.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Models\CatalogoObra;
use Models\Plataforma;
use Services\MatcherService;
use Services\RecomendacaoService;
use Services\TmdbService;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

set_time_limit(0);

$argv = $argv ?? [];
$opcoes = [
    'limite'         => 0,
    'trilha'         => '',
    'force'          => in_array('--force', $argv, true),
    'sem-provedores' => in_array('--sem-provedores', $argv, true),
    'dry-run'        => in_array('--dry-run', $argv, true),
    'recalcular'     => in_array('--recalcular', $argv, true),
    'piloto'         => in_array('--piloto', $argv, true),
    'creditos'       => in_array('--creditos', $argv, true),
];

foreach ($argv as $arg) {
    if (preg_match('/^--limite=(\d+)$/', $arg, $m) === 1) {
        $opcoes['limite'] = (int) $m[1];
    }

    if (preg_match('/^--trilha=([a-z0-9-]+)$/', $arg, $m) === 1) {
        $opcoes['trilha'] = $m[1];
    }
}

if ($opcoes['piloto']) {
    $opcoes['trilha'] = $opcoes['trilha'] !== '' ? $opcoes['trilha'] : 'coreia-doramas';
    $opcoes['limite'] = $opcoes['limite'] > 0 ? $opcoes['limite'] : 30;
    $opcoes['sem-provedores'] = true;
}

$recConfig = require dirname(__DIR__) . '/config/recomendacao.php';
$pdo = Database::pdo();
$tmdb = new TmdbService($pdo, $config['tmdb']);
$recomendacao = new RecomendacaoService($recConfig, new MatcherService(
    (float) ($config['raridade']['limiar'] ?? 12.0),
    (int) ($config['raridade']['min_base'] ?? 100)
));

if (!$tmdb->disponivel()) {
    fwrite(STDERR, "TMDB_API_KEY ausente. Configure no .env e recrie o container.\n");
    exit(1);
}

$regiao = (string) ($recConfig['regiao'] ?? 'BR');
$trilhas = (array) ($recConfig['trilhas'] ?? []);
$plataformasPorProvider = [];

foreach (Plataforma::todas($pdo) as $plataforma) {
    $plataformasPorProvider[(int) $plataforma['tmdb_provider_id']] = $plataforma;
}

$saida = static function (string $texto): void {
    echo $texto . PHP_EOL;
};

/**
 * Normaliza um resultado de discover para o denominador comum.
 *
 * @param array<string, mixed> $item
 * @return array{tmdb_id: int, tipo: string, trilha: string, titulo: string}
 */
$candidato = static function (array $item, string $tipo, string $trilha): ?array {
    $id = (int) ($item['id'] ?? 0);

    if ($id <= 0 || !empty($item['adult']) || !empty($item['softcore'])) {
        return null;
    }

    // Talk show, reality e telejornal não são repertório de cinema/série.
    $generosItem = array_map('intval', (array) ($item['genre_ids'] ?? []));

    if (array_intersect($generosItem, [10763, 10764, 10767]) !== []) {
        return null;
    }

    $titulo = (string) ($tipo === 'filme' ? ($item['title'] ?? '') : ($item['name'] ?? ''));

    if (trim($titulo) === '') {
        return null;
    }

    return [
        'tmdb_id' => $id,
        'tipo'    => $tipo,
        'trilha'  => $trilha,
        'titulo'  => $titulo,
    ];
};

/**
 * Percorre páginas de discover e devolve candidatos.
 *
 * @param array<string, mixed> $trilha
 * @return list<array<string, mixed>>
 */
$descobrir = static function (
    TmdbService $tmdb,
    array $trilha,
    string $tipo,
    int $limite,
    string $trilhaSlug
) use ($candidato, $saida): array {
    $porPagina = 20;
    $paginas = (int) ceil(($limite * 1.35) / $porPagina);
    $paginas = max(1, min(25, $paginas));
    $coletados = [];
    $tipoInterno = $tipo === 'movie' ? 'filme' : 'serie';

    for ($pagina = 1; $pagina <= $paginas; $pagina++) {
        $params = [
            'sort_by'        => 'popularity.desc',
            'vote_count.gte' => 20,
            'include_adult'  => 'false',
            'page'           => $pagina,
        ];

        $paises = (array) ($trilha['paises'] ?? []);

        if ($paises !== []) {
            $params['with_origin_country'] = implode('|', $paises);
        }

        $generos = (array) ($trilha['generos'] ?? []);

        if ($generos !== []) {
            $params['with_genres'] = implode(',', $generos);
        }

        $semGeneros = (array) ($trilha['sem_generos'] ?? []);

        if ($semGeneros !== []) {
            $params['without_genres'] = implode(',', $semGeneros);
        }

        $resposta = $tmdb->buscar('/discover/' . $tipo, $params);
        $resultados = $resposta['results'] ?? [];

        if (!is_array($resultados) || $resultados === []) {
            break;
        }

        foreach ($resultados as $item) {
            if (!is_array($item)) {
                continue;
            }

            $novo = $candidato($item, $tipoInterno, $trilhaSlug);

            if ($novo !== null && !isset($coletados[$novo['tipo'] . ':' . $novo['tmdb_id']])) {
                $coletados[$novo['tipo'] . ':' . $novo['tmdb_id']] = $novo;
            }
        }

        if (count($coletados) >= $limite) {
            break;
        }
    }

    $saida(sprintf('  %s/%s: %d candidatos', $trilhaSlug, $tipo, count($coletados)));

    return array_slice(array_values($coletados), 0, $limite);
};

/**
 * Grava (ou atualiza) uma obra e suas relações.
 *
 * @param array<string, mixed> $detalhe resposta de /movie/{id} ou /tv/{id}
 * @return int id interno
 */
$gravar = static function (
    PDO $pdo,
    RecomendacaoService $recomendacao,
    array $detalhe,
    string $tipo,
    string $regiao,
    array $plataformasPorProvider,
    array &$logos,
    bool $dryRun
) use ($recConfig): ?int {
    $tmdbId = (int) ($detalhe['id'] ?? 0);

    if ($tmdbId <= 0) {
        return null;
    }

    $nomeChave = $tipo === 'filme' ? 'title' : 'name';
    $originalChave = $tipo === 'filme' ? 'original_title' : 'original_name';
    $dataChave = $tipo === 'filme' ? 'release_date' : 'first_air_date';

    $titulo = trim((string) ($detalhe[$nomeChave] ?? ''));

    if ($titulo === '') {
        return null;
    }

    $ano = null;

    if (preg_match('/^(\d{4})/', (string) ($detalhe[$dataChave] ?? ''), $m) === 1) {
        $ano = (int) $m[1];
    }

    $generos = [];

    foreach ((array) ($detalhe['genres'] ?? []) as $genero) {
        $id = (int) ($genero['id'] ?? 0);
        $nome = trim((string) ($genero['name'] ?? ''));

        if ($id > 0 && $nome !== '') {
            $generos[$id] = $nome;
        }
    }

    if ($tipo === 'serie' && array_intersect(array_keys($generos), [10763, 10764, 10767]) !== []) {
        return null;
    }

    $paises = [];

    foreach ((array) ($detalhe['production_countries'] ?? []) as $pais) {
        $codigo = strtoupper(trim((string) ($pais['iso_3166_1'] ?? '')));

        if (preg_match('/^[A-Z]{2}$/', $codigo) === 1) {
            $paises[$codigo] = trim((string) ($pais['name'] ?? $codigo));
        }
    }

    foreach ((array) ($detalhe['origin_country'] ?? []) as $codigo) {
        $codigo = strtoupper(trim((string) $codigo));

        if (preg_match('/^[A-Z]{2}$/', $codigo) === 1 && !isset($paises[$codigo])) {
            $paises[$codigo] = (string) ($recConfig['paises'][$codigo]['nome'] ?? $codigo);
        }
    }

    $idioma = strtolower(trim((string) ($detalhe['original_language'] ?? '')));
    $duracao = null;

    if ($tipo === 'filme' && isset($detalhe['runtime']) && is_numeric($detalhe['runtime'])) {
        $duracao = (int) $detalhe['runtime'] > 0 ? (int) $detalhe['runtime'] : null;
    }

    if ($tipo === 'serie') {
        $tempos = array_values(array_filter((array) ($detalhe['episode_run_time'] ?? []), 'is_numeric'));

        if ($tempos !== []) {
            $duracao = (int) $tempos[0] > 0 ? (int) $tempos[0] : null;
        }
    }

    $sinopse = trim((string) ($detalhe['overview'] ?? ''));

    if (mb_strlen($sinopse) > 560) {
        $sinopse = mb_substr($sinopse, 0, 560);
        $sinopse = mb_substr($sinopse, 0, (int) mb_strrpos($sinopse, ' ')) . '…';
    }

    $temporadas = $tipo === 'serie' && isset($detalhe['number_of_seasons'])
        ? max(0, (int) $detalhe['number_of_seasons'])
        : null;

    $atributos = [
        'generos' => array_keys($generos),
        'paises'  => array_keys($paises),
        'idioma'  => $idioma,
        'ano'     => $ano,
        'tipo'    => $tipo,
    ];

    $vetor = $recomendacao->construir($atributos);
    $colunas = \Core\Vetor::paraColunasLegado($vetor['vetor']);

    $dados = [
        'tmdb_id'         => $tmdbId,
        'tipo'            => $tipo,
        'titulo'          => $titulo,
        'titulo_original' => trim((string) ($detalhe[$originalChave] ?? '')) ?: null,
        'ano'             => $ano,
        'sinopse'         => $sinopse !== '' ? $sinopse : null,
        'duracao'         => $duracao,
        'temporadas'      => $temporadas,
        'poster_path'     => $detalhe['poster_path'] ?? null,
        'backdrop_path'   => $detalhe['backdrop_path'] ?? null,
        'nota'            => isset($detalhe['vote_average']) ? round((float) $detalhe['vote_average'], 1) : null,
        'votos'           => (int) ($detalhe['vote_count'] ?? 0),
        'popularidade'    => (float) ($detalhe['popularity'] ?? 0),
        'idioma_original' => $idioma !== '' ? $idioma : null,
        'confianca'       => $vetor['confianca'],
    ] + $colunas;

    if ($dryRun) {
        return 0;
    }

    $campos = array_keys($dados);
    $marcadores = array_map(static fn (string $campo): string => ':' . $campo, $campos);
    $atualizacoes = array_map(
        static fn (string $campo): string => $campo . ' = VALUES(' . $campo . ')',
        array_filter($campos, static fn (string $campo): bool => !in_array($campo, ['tmdb_id', 'tipo'], true))
    );

    $sql = 'INSERT INTO catalogo_obras (' . implode(', ', $campos) . ', atualizado_em)
            VALUES (' . implode(', ', $marcadores) . ', NOW())
            ON DUPLICATE KEY UPDATE ' . implode(', ', $atualizacoes) . ', atualizado_em = NOW()';

    $pdo->prepare($sql)->execute($dados);

    $stmt = $pdo->prepare(
        'SELECT id FROM catalogo_obras WHERE tipo = :tipo AND tmdb_id = :tmdb LIMIT 1'
    );
    $stmt->execute(['tipo' => $tipo, 'tmdb' => $tmdbId]);
    $obraId = (int) $stmt->fetchColumn();

    if ($obraId <= 0) {
        return null;
    }

    $pdo->prepare('DELETE FROM catalogo_obra_generos WHERE obra_id = :id')->execute(['id' => $obraId]);
    $inserirGenero = $pdo->prepare(
        'INSERT INTO catalogo_generos (id, slug, nome) VALUES (:id, :slug, :nome)
         ON DUPLICATE KEY UPDATE nome = VALUES(nome)'
    );
    $ligarGenero = $pdo->prepare(
        'INSERT IGNORE INTO catalogo_obra_generos (obra_id, genero_id) VALUES (:obra, :genero)'
    );
    $mapaGeneros = (array) ($recConfig['generos'] ?? []);

    foreach ($generos as $id => $nome) {
        $slug = (string) ($mapaGeneros[$id]['slug'] ?? '');
        $slug = $slug !== '' ? $slug : (string) preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) ?: $nome));
        $slug = trim($slug, '-');

        $inserirGenero->execute(['id' => $id, 'slug' => $slug, 'nome' => $nome]);
        $ligarGenero->execute(['obra' => $obraId, 'genero' => $id]);
    }

    $pdo->prepare('DELETE FROM catalogo_obra_paises WHERE obra_id = :id')->execute(['id' => $obraId]);
    $inserirPais = $pdo->prepare(
        'INSERT INTO catalogo_paises (codigo, nome) VALUES (:codigo, :nome)
         ON DUPLICATE KEY UPDATE nome = VALUES(nome)'
    );
    $ligarPais = $pdo->prepare(
        'INSERT IGNORE INTO catalogo_obra_paises (obra_id, pais_codigo) VALUES (:obra, :pais)'
    );

    foreach ($paises as $codigo => $nome) {
        $inserirPais->execute(['codigo' => $codigo, 'nome' => $nome !== '' ? $nome : $codigo]);
        $ligarPais->execute(['obra' => $obraId, 'pais' => $codigo]);
    }

    // Sem `append_to_response`, não toca na disponibilidade já coletada.
    if (!array_key_exists('watch/providers', $detalhe)) {
        return $obraId;
    }

    $ofertas = (array) ($detalhe['watch/providers']['results'][$regiao] ?? []);

    $pdo->prepare('DELETE FROM catalogo_obra_plataformas WHERE obra_id = :id')->execute(['id' => $obraId]);
    $ligarPlataforma = $pdo->prepare(
        'INSERT IGNORE INTO catalogo_obra_plataformas (obra_id, plataforma_id, oferta, regiao, atualizado_em)
         VALUES (:obra, :plataforma, :oferta, :regiao, NOW())'
    );

    foreach (['flatrate', 'free', 'ads'] as $tipoOferta) {
        foreach ((array) ($ofertas[$tipoOferta] ?? []) as $oferta) {
            $providerId = (int) ($oferta['provider_id'] ?? 0);
            $plataforma = $plataformasPorProvider[$providerId] ?? null;

            if ($plataforma === null) {
                continue;
            }

            $logos[(int) $plataforma['id']] = $oferta['logo_path'] ?? null;

            $ligarPlataforma->execute([
                'obra'       => $obraId,
                'plataforma' => (int) $plataforma['id'],
                'oferta'     => $tipoOferta,
                'regiao'     => $regiao,
            ]);
        }
    }

    return $obraId;
};

/* -------------------------------------------------------------------------
 * Modo recalcular: só refaz vetor/confiança das obras já gravadas.
 * ---------------------------------------------------------------------- */
if ($opcoes['recalcular']) {
    $linhas = $pdo->query(
        'SELECT o.id, o.tipo, o.ano, o.idioma_original,
                GROUP_CONCAT(DISTINCT og.genero_id) AS generos,
                GROUP_CONCAT(DISTINCT op.pais_codigo) AS paises
           FROM catalogo_obras o
           LEFT JOIN catalogo_obra_generos og ON og.obra_id = o.id
           LEFT JOIN catalogo_obra_paises op ON op.obra_id = o.id
          GROUP BY o.id'
    )->fetchAll();

    $atualizar = $pdo->prepare(
        'UPDATE catalogo_obras SET eixo_1 = :e1, eixo_2 = :e2, eixo_3 = :e3, eixo_4 = :e4,
                eixo_5 = :e5, eixo_6 = :e6, eixo_7 = :e7, eixo_8 = :e8,
                confianca = :confianca, atualizado_em = atualizado_em
          WHERE id = :id'
    );

    $total = 0;

    foreach ($linhas as $linha) {
        $vetor = $recomendacao->construir([
            'generos' => array_filter(explode(',', (string) $linha['generos'])),
            'paises'  => array_filter(explode(',', (string) $linha['paises'])),
            'idioma'  => $linha['idioma_original'],
            'ano'     => $linha['ano'],
            'tipo'    => $linha['tipo'],
        ]);

        $params = \Core\Vetor::paraColunasLegado($vetor['vetor']);
        $params['confianca'] = $vetor['confianca'];
        $params['id'] = (int) $linha['id'];
        $atualizar->execute($params);
        $total++;
    }

    $saida(sprintf('Vetores recalculados: %d obras.', $total));
    exit(0);
}


/* -------------------------------------------------------------------------
 * Modo créditos: elenco (top 10) e direção dos títulos mais populares.
 * Roda depois da ingestão, com uma chamada por obra.
 * ---------------------------------------------------------------------- */
if ($opcoes['creditos']) {
    $limiteCreditos = $opcoes['limite'] > 0 ? $opcoes['limite'] : 800;
    $obras = $pdo->query(
        'SELECT id, tmdb_id, tipo, titulo
           FROM catalogo_obras
          WHERE ativo = 1
          ORDER BY popularidade DESC, id
          LIMIT ' . (int) $limiteCreditos
    )->fetchAll();

    $temCreditos = $pdo->prepare('SELECT COUNT(*) FROM catalogo_creditos WHERE obra_id = :id');
    $inserirPessoa = $pdo->prepare(
        'INSERT INTO catalogo_pessoas (tmdb_id, nome, foto_path, popularidade, atualizado_em)
         VALUES (:tmdb, :nome, :foto, :popularidade, NOW())
         ON DUPLICATE KEY UPDATE nome = VALUES(nome), foto_path = VALUES(foto_path),
                                 popularidade = VALUES(popularidade), atualizado_em = NOW()'
    );
    $buscarPessoa = $pdo->prepare('SELECT id FROM catalogo_pessoas WHERE tmdb_id = :tmdb LIMIT 1');
    $limparCreditos = $pdo->prepare('DELETE FROM catalogo_creditos WHERE obra_id = :id');
    $inserirCredito = $pdo->prepare(
        'INSERT INTO catalogo_creditos (obra_id, pessoa_id, funcao, personagem, ordem)
         VALUES (:obra, :pessoa, :funcao, :personagem, :ordem)
         ON DUPLICATE KEY UPDATE personagem = VALUES(personagem), ordem = VALUES(ordem)'
    );

    $gravadas = 0;
    $ignoradas = 0;
    $falhas = 0;

    foreach ($obras as $obra) {
        $obraId = (int) $obra['id'];

        if (!$opcoes['force']) {
            $temCreditos->execute(['id' => $obraId]);

            if ((int) $temCreditos->fetchColumn() > 0) {
                $ignoradas++;
                continue;
            }
        }

        $tipoApi = $obra['tipo'] === 'serie' ? 'tv' : 'movie';
        $caminho = '/' . $tipoApi . '/' . (int) $obra['tmdb_id'] . '/credits';

        if ($tipoApi === 'tv') {
            $caminho = '/tv/' . (int) $obra['tmdb_id'] . '/aggregate_credits';
        }

        $detalhe = $tmdb->buscar($caminho);

        if ($detalhe === null) {
            $falhas++;
            continue;
        }

        $elenco = [];

        foreach (array_slice((array) ($detalhe['cast'] ?? []), 0, 10) as $ordem => $ator) {
            $pessoaTmdb = (int) ($ator['id'] ?? 0);

            if ($pessoaTmdb <= 0) {
                continue;
            }

            $personagem = (string) ($ator['character'] ?? '');

            if ($personagem === '' && isset($ator['roles'][0]['character'])) {
                $personagem = (string) $ator['roles'][0]['character'];
            }

            $elenco[] = [
                'tmdb'       => $pessoaTmdb,
                'nome'       => trim((string) ($ator['name'] ?? '')),
                'foto'       => $ator['profile_path'] ?? null,
                'popularidade' => (float) ($ator['popularity'] ?? 0),
                'funcao'     => 'elenco',
                'personagem' => $personagem !== '' ? mb_substr($personagem, 0, 160) : null,
                'ordem'      => $ordem,
            ];
        }

        foreach (array_slice((array) ($detalhe['crew'] ?? []), 0, 20) as $membro) {
            $pessoaTmdb = (int) ($membro['id'] ?? 0);

            if ($pessoaTmdb <= 0) {
                continue;
            }

            $ehDiretor = ($membro['job'] ?? '') === 'Director';

            if (!$ehDiretor && isset($membro['jobs'])) {
                foreach ((array) $membro['jobs'] as $trabalho) {
                    if (($trabalho['job'] ?? '') === 'Director') {
                        $ehDiretor = true;
                        break;
                    }
                }
            }

            if (!$ehDiretor) {
                continue;
            }

            $elenco[] = [
                'tmdb'       => $pessoaTmdb,
                'nome'       => trim((string) ($membro['name'] ?? '')),
                'foto'       => $membro['profile_path'] ?? null,
                'popularidade' => (float) ($membro['popularity'] ?? 0),
                'funcao'     => 'direcao',
                'personagem' => null,
                'ordem'      => 0,
            ];
        }

        if ($elenco === []) {
            $ignoradas++;
            continue;
        }

        if (!$opcoes['dry-run']) {
            $pdo->beginTransaction();
        }

        try {
            if (!$opcoes['dry-run']) {
                $limparCreditos->execute(['id' => $obraId]);
            }

            foreach ($elenco as $credito) {
                if ($opcoes['dry-run']) {
                    continue;
                }

                $inserirPessoa->execute([
                    'tmdb'         => $credito['tmdb'],
                    'nome'         => $credito['nome'] !== '' ? $credito['nome'] : 'Sem nome',
                    'foto'         => $credito['foto'],
                    'popularidade' => $credito['popularidade'],
                ]);
                $buscarPessoa->execute(['tmdb' => $credito['tmdb']]);
                $pessoaId = (int) $buscarPessoa->fetchColumn();

                if ($pessoaId <= 0) {
                    continue;
                }

                $inserirCredito->execute([
                    'obra'       => $obraId,
                    'pessoa'     => $pessoaId,
                    'funcao'     => $credito['funcao'],
                    'personagem' => $credito['personagem'],
                    'ordem'      => $credito['ordem'],
                ]);
            }

            if (!$opcoes['dry-run']) {
                $pdo->commit();
            }
        } catch (\Throwable $erro) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $falhas++;
            fwrite(STDERR, sprintf("  falha ao gravar créditos de %s: %s\n", $obra['titulo'], $erro->getMessage()));
            continue;
        }

        $gravadas++;

        if ($gravadas % 20 === 0) {
            $saida(sprintf('  %d obras com créditos (última: %s)...', $gravadas, $obra['titulo']));
        }

        usleep(40000);
    }

    $saida(sprintf(
        'Créditos concluídos: %d obras gravadas, %d ignoradas, %d falhas. Pessoas: %d.',
        $gravadas,
        $ignoradas,
        $falhas,
        (int) $pdo->query('SELECT COUNT(*) FROM catalogo_pessoas')->fetchColumn()
    ));
    exit(0);
}

/* -------------------------------------------------------------------------
 * 1. Descoberta
 * ---------------------------------------------------------------------- */
$saida('PersonaCut · catálogo TMDB');
$saida(sprintf('Região: %s · plataformas cadastradas: %d', $regiao, count($plataformasPorProvider)));

$candidatos = [];

$trilhasAlvo = $opcoes['trilha'] !== ''
    ? array_intersect_key($trilhas, [$opcoes['trilha'] => true])
    : $trilhas;

if ($trilhasAlvo === []) {
    fwrite(STDERR, "Trilha não encontrada: {$opcoes['trilha']}\n");
    exit(1);
}

foreach ($trilhasAlvo as $slug => $trilha) {
    $limite = $opcoes['limite'] > 0 ? $opcoes['limite'] : (int) ($trilha['limite'] ?? 100);
    $tipo = (string) ($trilha['tipo'] ?? 'ambos');
    $tipos = $tipo === 'ambos' ? ['movie', 'tv'] : [$tipo === 'filme' ? 'movie' : 'tv'];

    foreach ($tipos as $tipoApi) {
        $tipoInterno = $tipoApi === 'movie' ? 'filme' : 'serie';
        $lista = $descobrir($tmdb, $trilha, $tipoApi, $limite, (string) $slug);

        foreach ($lista as $item) {
            $chave = $item['tipo'] . ':' . $item['tmdb_id'];

            if (!isset($candidatos[$chave])) {
                $candidatos[$chave] = $item;
            }
        }
    }
}

// Cobertura dirigida para plataformas de nicho (MUBI, Crunchyroll, Looke...).
if ($opcoes['trilha'] === '') {
    foreach ((array) ($recConfig['cobertura_plataformas'] ?? []) as $providerId => $quantidade) {
        $plataforma = $plataformasPorProvider[(int) $providerId] ?? null;

        if ($plataforma === null) {
            continue;
        }

        foreach (['movie', 'tv'] as $tipoApi) {
            $tipoInterno = $tipoApi === 'movie' ? 'filme' : 'serie';
            $paginas = max(1, min(15, (int) ceil(((int) $quantidade) / 20)));

            for ($pagina = 1; $pagina <= $paginas; $pagina++) {
                $resposta = $tmdb->buscar('/discover/' . $tipoApi, [
                    'sort_by'                => 'popularity.desc',
                    'vote_count.gte'         => 20,
                    'watch_region'           => $regiao,
                    'with_watch_providers'   => (int) $providerId,
                    'with_watch_monetization_types' => 'flatrate',
                    'page'                   => $pagina,
                ]);
                $resultados = $resposta['results'] ?? [];

                if (!is_array($resultados) || $resultados === []) {
                    break;
                }

                foreach ($resultados as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $novo = $candidato($item, $tipoInterno, '');

                    if ($novo !== null) {
                        $chave = $novo['tipo'] . ':' . $novo['tmdb_id'];
                        $candidatos[$chave] ??= $novo;
                    }
                }
            }
        }

        $saida(sprintf('  cobertura %s: +%d candidatos', $plataforma['nome'], count($candidatos)));
    }
}

$saida(sprintf('Total de candidatos: %d', count($candidatos)));

/* -------------------------------------------------------------------------
 * 2. Detalhe + gravação
 * ---------------------------------------------------------------------- */
$existentes = [];

foreach ($pdo->query('SELECT id, tipo, tmdb_id, atualizado_em FROM catalogo_obras')->fetchAll() as $linha) {
    $existentes[$linha['tipo'] . ':' . (int) $linha['tmdb_id']] = $linha;
}

$gravadas = 0;
$ignoradas = 0;
$falhas = 0;
$logos = [];
$limiteProcesso = $opcoes['limite'] > 0 ? $opcoes['limite'] : 0;

foreach ($candidatos as $chave => $item) {
    if ($limiteProcesso > 0 && $gravadas >= $limiteProcesso) {
        break;
    }

    $existente = $existentes[$chave] ?? null;

    if ($existente !== null && !$opcoes['force']) {
        // Metadados e disponibilidade são revalidados a cada 90 dias.
        $ultima = $existente['atualizado_em'] !== null ? strtotime((string) $existente['atualizado_em']) : 0;

        if ($ultima > 0 && (time() - $ultima) < 90 * 86400) {
            $ignoradas++;
            continue;
        }
    }

    $caminho = ($item['tipo'] === 'filme' ? '/movie/' : '/tv/') . $item['tmdb_id'];

    if ($opcoes['sem-provedores']) {
        $detalhe = $tmdb->buscar($caminho);
    } else {
        $detalhe = $tmdb->buscar($caminho, ['append_to_response' => 'watch/providers']);
    }

    if ($detalhe === null) {
        $falhas++;
        fwrite(STDERR, sprintf("  falhou: %s\n", $caminho));
        continue;
    }

    $id = $gravar(
        $pdo,
        $recomendacao,
        $detalhe,
        $item['tipo'],
        $regiao,
        $plataformasPorProvider,
        $logos,
        (bool) $opcoes['dry-run']
    );

    if ($id === null) {
        $falhas++;
        continue;
    }

    $gravadas++;

    if ($gravadas % 20 === 0) {
        $saida(sprintf('  %d gravadas (última: %s)...', $gravadas, $item['titulo']));
    }

    usleep(40000);
}

if (!$opcoes['dry-run']) {
    foreach ($logos as $plataformaId => $logo) {
        Plataforma::atualizarLogo($pdo, (int) $plataformaId, is_string($logo) ? $logo : null);
    }
}

$saida(sprintf(
    'Concluído: %d gravadas, %d ignoradas (recentes), %d falhas. Catálogo: %d obras.',
    $gravadas,
    $ignoradas,
    $falhas,
    CatalogoObra::total($pdo)
));
