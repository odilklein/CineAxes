<?php

declare(strict_types=1);

/**
 * Enriquecimento do catálogo via TMDB.
 *
 *   docker compose exec app php bin/tmdb_sync.php
 *   docker compose exec app php bin/tmdb_sync.php --force
 *
 * Preenche imagem_url, ator_foto_url, país, nascimento, popularidade e tmdb_id
 * das entidades, além dos pôsteres de entidades_obras. Usa cache_tmdb.
 */

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use Core\Database;
use Services\TmdbService;

$pdo = Database::pdo();
$tmdb = new TmdbService($pdo, $config['tmdb']);

if (!$tmdb->disponivel()) {
    fwrite(STDERR, "TMDB_API_KEY ausente. Configure no .env e recrie o container.\n");
    exit(1);
}

$forcar = in_array('--force', $argv ?? [], true);

$paises = [
    'US' => 'Estados Unidos', 'BR' => 'Brasil', 'GB' => 'Reino Unido', 'JP' => 'Japão',
    'FR' => 'França', 'CA' => 'Canadá', 'DE' => 'Alemanha', 'IT' => 'Itália',
    'ES' => 'Espanha', 'KR' => 'Coreia do Sul', 'IN' => 'Índia', 'MX' => 'México',
    'AR' => 'Argentina', 'SE' => 'Suécia', 'DK' => 'Dinamarca', 'AU' => 'Austrália',
    'CN' => 'China', 'HK' => 'Hong Kong', 'TW' => 'Taiwan', 'NZ' => 'Nova Zelândia',
    'IE' => 'Irlanda', 'NL' => 'Países Baixos', 'RU' => 'Rússia', 'PL' => 'Polônia',
    'CZ' => 'Chéquia', 'BE' => 'Bélgica', 'CH' => 'Suíça', 'PT' => 'Portugal',
];

$anoDe = static function (?string $data): ?int {
    if ($data === null || !preg_match('/(19|20)\d{2}/', $data, $m)) {
        return null;
    }

    return (int) $m[0];
};

/**
 * @param array<string, mixed> $campos
 * @param array<string, mixed> $params
 */
$atualizar = static function (PDO $pdo, int $id, array $campos, array $params): void {
    if ($campos === []) {
        return;
    }

    $params['id'] = $id;
    $sql = 'UPDATE entidades_match SET ' . implode(', ', $campos) . ', enriquecido_em = NOW() WHERE id = :id';
    $pdo->prepare($sql)->execute($params);
};

$entidades = $pdo->query(
    'SELECT id, tipo, nome, obra_origem, ano, ator_nome, tmdb_id, imagem_url, enriquecido_em
       FROM entidades_match
      ORDER BY FIELD(tipo, "personagem", "diretor", "estudio"), nome'
)->fetchAll();

$total = 0;
$falhas = 0;

foreach ($entidades as $entidade) {
    if (!$forcar && $entidade['enriquecido_em'] !== null && $entidade['imagem_url'] !== null) {
        continue;
    }

    $id = (int) $entidade['id'];
    $nome = (string) $entidade['nome'];
    $campos = [];
    $params = [];
    $log = [];

    if ($entidade['tipo'] === 'personagem') {
        // Pôster do filme que define o personagem.
        $filme = $tmdb->procurarFilme((string) $entidade['obra_origem'], $entidade['ano'] !== null ? (int) $entidade['ano'] : null);

        if ($filme !== null) {
            $detalhe = $tmdb->filme((int) $filme['id']) ?? $filme;
            $poster = $tmdb->imagem($detalhe['poster_path'] ?? $filme['poster_path'] ?? null, 'w500');

            if ($poster !== null) {
                $campos[] = 'imagem_url = :imagem';
                $params['imagem'] = $poster;
            }

            $campos[] = 'tmdb_id = :tmdb';
            $params['tmdb'] = (int) $filme['id'];
            $campos[] = 'popularidade = :pop';
            $params['pop'] = round((float) ($detalhe['popularity'] ?? $filme['popularity'] ?? 0), 2);
            $log[] = 'filme #' . $filme['id'];
        }

        // Foto e dados do intérprete.
        if (!empty($entidade['ator_nome'])) {
            $pessoa = $tmdb->procurarPessoa((string) $entidade['ator_nome']);

            if ($pessoa !== null) {
                $detalhe = $tmdb->pessoa((int) $pessoa['id']) ?? $pessoa;
                $foto = $tmdb->imagem($detalhe['profile_path'] ?? $pessoa['profile_path'] ?? null, 'w300');

                if ($foto !== null) {
                    $campos[] = 'ator_foto_url = :ator_foto';
                    $params['ator_foto'] = $foto;
                }

                $campos[] = 'ator_tmdb_id = :ator_id';
                $params['ator_id'] = (int) $pessoa['id'];

                $local = trim((string) ($detalhe['place_of_birth'] ?? ''));
                if ($local !== '') {
                    $campos[] = 'pais = :pais';
                    $params['pais'] = mb_substr($local, 0, 80);
                }

                $nascimento = $anoDe($detalhe['birthday'] ?? null);
                if ($nascimento !== null) {
                    $campos[] = 'nascimento = :nasc';
                    $params['nasc'] = $nascimento;
                }

                $log[] = 'ator #' . $pessoa['id'];
            }
        }
    } elseif ($entidade['tipo'] === 'diretor') {
        $pessoa = $tmdb->procurarPessoa($nome);

        if ($pessoa !== null) {
            $detalhe = $tmdb->pessoa((int) $pessoa['id']) ?? $pessoa;
            $foto = $tmdb->imagem($detalhe['profile_path'] ?? $pessoa['profile_path'] ?? null, 'w500');

            if ($foto !== null) {
                $campos[] = 'imagem_url = :imagem';
                $params['imagem'] = $foto;
            }

            $campos[] = 'tmdb_id = :tmdb';
            $params['tmdb'] = (int) $pessoa['id'];
            $campos[] = 'popularidade = :pop';
            $params['pop'] = round((float) ($detalhe['popularity'] ?? $pessoa['popularity'] ?? 0), 2);

            $local = trim((string) ($detalhe['place_of_birth'] ?? ''));
            if ($local !== '') {
                $campos[] = 'pais = :pais';
                $params['pais'] = mb_substr($local, 0, 80);
            }

            $nascimento = $anoDe($detalhe['birthday'] ?? null);
            if ($nascimento !== null) {
                $campos[] = 'nascimento = :nasc';
                $params['nasc'] = $nascimento;
            }

            $log[] = 'pessoa #' . $pessoa['id'];
        }
    } else {
        $empresa = $tmdb->procurarEmpresa($nome);

        if ($empresa !== null) {
            $detalhe = $tmdb->empresa((int) $empresa['id']) ?? $empresa;
            $campos[] = 'tmdb_id = :tmdb';
            $params['tmdb'] = (int) $empresa['id'];

            $logos = $tmdb->logosEmpresa((int) $empresa['id']);
            $logo = $logos[0]['file_path'] ?? null;

            if ($logo !== null) {
                $campos[] = 'imagem_url = :imagem';
                $params['imagem'] = $tmdb->imagem($logo, 'w300');
            }

            $codigo = strtoupper((string) ($detalhe['origin_country'] ?? ''));
            if ($codigo !== '') {
                $campos[] = 'pais = :pais';
                $params['pais'] = $paises[$codigo] ?? $codigo;
            }

            $log[] = 'empresa #' . $empresa['id'];
        }
    }

    try {
        $atualizar($pdo, $id, $campos, $params);
        $total++;
        printf("  ✓ %-10s %-32s %s\n", $entidade['tipo'], $nome, $log !== [] ? implode(', ', $log) : 'sem correspondência');
    } catch (Throwable $erro) {
        $falhas++;
        fwrite(STDERR, "  ✗ {$entidade['tipo']} {$nome}: {$erro->getMessage()}\n");
    }
}

// Pôsteres das obras essenciais.
$obras = $pdo->query(
    'SELECT o.id, o.titulo, o.ano, o.tmdb_id, o.poster_url, e.nome AS entidade
       FROM entidades_obras o
       JOIN entidades_match e ON e.id = o.entidade_id
      ORDER BY e.nome, o.ordem'
)->fetchAll();

$obrasOk = 0;

foreach ($obras as $obra) {
    if (!$forcar && $obra['tmdb_id'] !== null && $obra['poster_url'] !== null) {
        continue;
    }

    $filme = $tmdb->procurarFilme((string) $obra['titulo'], $obra['ano'] !== null ? (int) $obra['ano'] : null);

    if ($filme === null) {
        fwrite(STDERR, "  · obra sem correspondência: {$obra['titulo']} ({$obra['ano']})\n");
        continue;
    }

    $detalhe = $tmdb->filme((int) $filme['id']) ?? $filme;
    $poster = $tmdb->imagem($detalhe['poster_path'] ?? $filme['poster_path'] ?? null, 'w500');

    $pdo->prepare(
        'UPDATE entidades_obras SET tmdb_id = :tmdb, poster_url = :poster WHERE id = :id'
    )->execute([
        'tmdb' => (int) $filme['id'],
        'poster' => $poster,
        'id' => (int) $obra['id'],
    ]);

    $obrasOk++;
    printf("  ✓ obra      %-32s %s (%s)\n", $obra['titulo'], $obra['entidade'], (string) $obra['ano']);
}

printf("\n  %d entidades e %d obras enriquecidas. %d falha(s).\n", $total, $obrasOk, $falhas);
printf("  Gere o SQL com: docker compose exec -T app php bin/tmdb_export.php > sql/003_enriquecimento.sql\n");
