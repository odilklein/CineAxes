<?php

declare(strict_types=1);

namespace Models;

use Core\Vetor;
use PDO;

/**
 * Catálogo audiovisual (filmes e séries) usado nas recomendações.
 *
 * Uma requisição de perfil carrega o catálogo inteiro em 4 consultas (obras,
 * gêneros, países e plataformas) e agrupa em memória. Com ~2.500 títulos o
 * custo é de poucos milissegundos e evita N+1.
 */
final class CatalogoObra
{
    private const COLUNAS = 'id, tmdb_id, tipo, titulo, titulo_original, ano, sinopse,
                    duracao, temporadas, poster_path, backdrop_path, nota, votos,
                    popularidade, idioma_original, confianca, ativo, atualizado_em,
                    eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8';

    /**
     * @param bool $somenteComPlataforma ignora títulos sem disponibilidade
     * @return list<array<string, mixed>>
     */
    public static function todos(PDO $pdo, bool $somenteComPlataforma = false): array
    {
        $sql = 'SELECT ' . self::COLUNAS . '
                  FROM catalogo_obras
                 WHERE ativo = 1';

        if ($somenteComPlataforma) {
            $sql .= ' AND EXISTS (
                        SELECT 1
                          FROM catalogo_obra_plataformas opl
                          JOIN catalogo_plataformas p ON p.id = opl.plataforma_id
                         WHERE opl.obra_id = catalogo_obras.id
                           AND p.ativa = 1
                           AND opl.oferta = \'flatrate\'
                      )';
        }

        $sql .= ' ORDER BY popularidade DESC, titulo';

        $linhas = $pdo->query($sql)->fetchAll();

        if ($linhas === []) {
            return [];
        }

        $obras = [];

        foreach ($linhas as $linha) {
            $obra = self::normalizar($linha);
            $obra['generos'] = [];
            $obra['paises'] = [];
            $obra['plataformas'] = [];
            $obras[(int) $obra['id']] = $obra;
        }

        $generos = $pdo->query(
            'SELECT og.obra_id, g.id, g.nome, g.slug
               FROM catalogo_obra_generos og
               JOIN catalogo_generos g ON g.id = og.genero_id'
        )->fetchAll();

        foreach ($generos as $linha) {
            $id = (int) $linha['obra_id'];

            if (isset($obras[$id])) {
                $obras[$id]['generos'][] = [
                    'id'   => (int) $linha['id'],
                    'nome' => (string) $linha['nome'],
                    'slug' => (string) $linha['slug'],
                ];
            }
        }

        $paises = $pdo->query(
            'SELECT op.obra_id, p.codigo, p.nome
               FROM catalogo_obra_paises op
               JOIN catalogo_paises p ON p.codigo = op.pais_codigo'
        )->fetchAll();

        foreach ($paises as $linha) {
            $id = (int) $linha['obra_id'];

            if (isset($obras[$id])) {
                $obras[$id]['paises'][] = [
                    'codigo' => (string) $linha['codigo'],
                    'nome'   => (string) $linha['nome'],
                ];
            }
        }

        $plataformas = $pdo->query(
            'SELECT opl.obra_id, opl.oferta, p.id, p.nome, p.slug, p.logo_path
               FROM catalogo_obra_plataformas opl
               JOIN catalogo_plataformas p ON p.id = opl.plataforma_id
              WHERE p.ativa = 1
                AND opl.oferta = \'flatrate\''
        )->fetchAll();

        foreach ($plataformas as $linha) {
            $id = (int) $linha['obra_id'];

            if (isset($obras[$id])) {
                $obras[$id]['plataformas'][] = [
                    'id'     => (int) $linha['id'],
                    'nome'   => (string) $linha['nome'],
                    'slug'   => (string) $linha['slug'],
                    'logo'   => $linha['logo_path'],
                    'oferta' => (string) $linha['oferta'],
                ];
            }
        }

        foreach ($obras as &$obra) {
            $obra['plataformas_ids'] = array_values(array_unique(array_map(
                static fn (array $plataforma): int => (int) $plataforma['id'],
                $obra['plataformas']
            )));
            // A amplitude depende de país/idioma/década/gênero, que só estão
            // disponíveis depois dos joins acima.
            $obra['vetor'][5] = self::amplitude($obra);
        }
        unset($obra);

        if ($somenteComPlataforma) {
            $obras = array_filter(
                $obras,
                static fn (array $obra): bool => $obra['plataformas'] !== []
            );
        }

        return array_values($obras);
    }

    /**
     * Disponibilidade por tmdb_id, para enriquecer cards já existentes
     * (estante do perfil, leituras, quick view) sem carregar o catálogo todo.
     *
     * @param list<int> $tmdbIds
     * @param string $tipo 'filme' para a estante (as obras curadas são filmes)
     * @return array<int, list<array<string, mixed>>>
     */
    public static function plataformasPorTmdb(PDO $pdo, array $tmdbIds, string $tipo = 'filme'): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $tmdbIds))));

        if ($ids === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            'SELECT o.tmdb_id, o.tipo, opl.oferta, p.id, p.nome, p.slug, p.logo_path
               FROM catalogo_obras o
               JOIN catalogo_obra_plataformas opl ON opl.obra_id = o.id
               JOIN catalogo_plataformas p ON p.id = opl.plataforma_id
              WHERE o.tmdb_id IN (' . $marcadores . ')
                AND o.tipo = ?
                AND p.ativa = 1
                AND opl.oferta = \'flatrate\'
              ORDER BY o.tmdb_id, p.prioridade, p.nome'
        );
        $ids[] = $tipo;
        $stmt->execute($ids);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $chave = (int) $linha['tmdb_id'];
            $mapa[$chave][] = [
                'id'     => (int) $linha['id'],
                'nome'   => (string) $linha['nome'],
                'slug'   => (string) $linha['slug'],
                'logo'   => $linha['logo_path'],
                'oferta' => (string) $linha['oferta'],
            ];
        }

        return $mapa;
    }

    public static function total(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM catalogo_obras WHERE ativo = 1')->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public static function porId(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT ' . self::COLUNAS . ' FROM catalogo_obras WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();

        return $linha === false ? null : self::normalizar($linha);
    }

    /**
     * @param array<string, mixed> $linha
     * @return array<string, mixed>
     */
    private static function normalizar(array $linha): array
    {
        $linha['id'] = (int) $linha['id'];
        $linha['tmdb_id'] = (int) $linha['tmdb_id'];
        $linha['ano'] = $linha['ano'] === null ? null : (int) $linha['ano'];
        $linha['duracao'] = $linha['duracao'] === null ? null : (int) $linha['duracao'];
        $linha['temporadas'] = $linha['temporadas'] === null ? null : (int) $linha['temporadas'];
        $linha['nota'] = $linha['nota'] === null ? null : (float) $linha['nota'];
        $linha['votos'] = (int) $linha['votos'];
        $linha['popularidade'] = (float) $linha['popularidade'];
        $linha['confianca'] = (int) $linha['confianca'];
        $linha['ativo'] = (int) $linha['ativo'] === 1;
        $linha['vetor_legado'] = Vetor::daLinhaLegado($linha);
        $linha['vetor'] = Vetor::projetar6($linha['vetor_legado']);

        for ($i = 1; $i <= Vetor::EIXOS_LEGADO; $i++) {
            unset($linha['eixo_' . $i]);
        }

        return $linha;
    }

    /**
     * Escore heurístico de amplitude de uma obra (0 = circunscrita, 100 = omnívora).
     *
     * Não é uma medida do usuário: é um atributo da obra. Serve para o motor de
     * descoberta saber o quanto um título sai do circuito familiar (EUA/Brasil,
     * recente, gênero mainstream).
     */
    private static function amplitude(array $obra): float
    {
        $valor = 50.0;
        $paises = array_map(
            static fn (array $pais): string => strtoupper((string) ($pais['codigo'] ?? '')),
            (array) ($obra['paises'] ?? [])
        );
        $temEUA = in_array('US', $paises, true);
        $temBrasil = in_array('BR', $paises, true);

        if ($paises !== [] && !$temEUA && !$temBrasil) {
            $valor += 15;
        } elseif ($paises !== [] && !$temEUA) {
            $valor += 8;
        }

        $ano = (int) ($obra['ano'] ?? 0);

        if ($ano > 0 && $ano < 1980) {
            $valor += 12;
        } elseif ($ano > 0 && $ano < 2000) {
            $valor += 6;
        }

        $generos = array_map(
            static fn (array $genero): int => (int) ($genero['id'] ?? 0),
            (array) ($obra['generos'] ?? [])
        );

        if (array_intersect($generos, [99, 36, 10752, 37, 27]) !== []) {
            $valor += 8;
        }

        $idioma = strtolower((string) ($obra['idioma_original'] ?? ''));

        if ($idioma !== '' && !in_array($idioma, ['en', 'pt', 'es'], true)) {
            $valor += 5;
        }

        return round(max(0.0, min(100.0, $valor)), 2);
    }
}
