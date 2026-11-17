<?php

declare(strict_types=1);

namespace Services;

/**
 * Escolhe um clipe oficial de cena a partir dos vídeos da TMDB.
 *
 * O serviço é puro: não acessa banco nem rede. A busca fica no TmdbService e a
 * pontuação pode ser testada com fixtures, sem depender da API.
 */
final class SceneService
{
    /**
     * Normaliza a resposta de `/movie/{id}/videos` em uma lista enxuta.
     *
     * Aceita tanto o payload completo (`results`) quanto a lista de resultados.
     *
     * @param array<string, mixed>|list<array<string, mixed>> $payload
     * @return list<array<string, mixed>>
     */
    public static function candidatos(array $payload): array
    {
        $resultados = isset($payload['results']) && is_array($payload['results'])
            ? $payload['results']
            : $payload;

        $candidatos = [];

        foreach ($resultados as $item) {
            if (!is_array($item)) {
                continue;
            }

            $site = (string) ($item['site'] ?? '');
            $key = trim((string) ($item['key'] ?? ''));

            if (strcasecmp($site, 'YouTube') !== 0 || $key === '') {
                continue;
            }

            $candidatos[] = [
                'key'          => $key,
                'nome'         => trim((string) ($item['name'] ?? '')),
                'tipo'         => trim((string) ($item['type'] ?? '')),
                'oficial'      => (bool) ($item['official'] ?? false),
                'idioma'       => trim((string) ($item['iso_639_1'] ?? '')),
                'pais'         => trim((string) ($item['iso_3166_1'] ?? '')),
                'publicado_em' => trim((string) ($item['published_at'] ?? '')),
                'duracao'      => isset($item['duration']) && is_numeric($item['duration'])
                    ? (int) $item['duration']
                    : null,
            ];
        }

        return $candidatos;
    }

    /**
     * Ordena os candidatos por pontuação. Candidatos rejeitados (trailer,
     * featurette, preview, tipo diferente de Clip) não entram na lista.
     *
     * @param list<array<string, mixed>> $candidatos
     * @param array<string, mixed>       $perfil
     * @param array<string, mixed>       $config
     * @return list<array<string, mixed>> candidatos + score/motivos
     */
    public static function avaliarCandidatos(array $candidatos, array $perfil, array $config): array
    {
        $pesos = self::pesos($config);
        $ruins = array_map([self::class, 'normalizar'], self::lista($config['palavras_ruins'] ?? []));
        $genericas = array_map([self::class, 'normalizar'], self::lista($config['palavras_genericas'] ?? []));
        $palavras = array_map([self::class, 'normalizar'], self::lista($perfil['palavras_chave'] ?? []));
        $palavras = array_values(array_filter(array_unique($palavras), static fn (string $p): bool => $p !== ''));
        $titulo = self::normalizar((string) ($perfil['titulo'] ?? ''));

        $avaliados = [];

        foreach ($candidatos as $candidato) {
            if (self::normalizar((string) ($candidato['tipo'] ?? '')) !== 'clip') {
                continue;
            }

            $nome = self::normalizar((string) ($candidato['nome'] ?? ''));

            if ($nome === '' || self::contemAlguma($nome, $ruins)) {
                continue;
            }

            $score = 0.0;
            $motivos = [];

            if (!empty($candidato['oficial'])) {
                $score += $pesos['oficial'];
                $motivos[] = 'oficial';
            }

            $acertos = 0;

            foreach ($palavras as $palavra) {
                if ($palavra !== '' && str_contains($nome, $palavra)) {
                    $acertos++;
                    $motivos[] = 'palavra:' . $palavra;
                }
            }

            if ($acertos > 0) {
                $score += min($pesos['max_palavras'], $acertos * $pesos['palavra_chave']);
            }

            if ($titulo !== '' && strlen($titulo) >= 3 && str_contains($nome, $titulo)) {
                $score += $pesos['titulo'];
                $motivos[] = 'titulo';
            }

            $idioma = self::normalizar((string) ($candidato['idioma'] ?? ''));

            if ($idioma === 'pt') {
                $score += $pesos['idioma_pt'];
                $motivos[] = 'pt';
            } elseif ($idioma === 'en') {
                $score += $pesos['idioma_en'];
                $motivos[] = 'en';
            }

            if (self::ehGenerico($nome, $genericas)) {
                $score += $pesos['generico'];
                $motivos[] = 'nome genérico';
            }

            $candidato['score'] = $score;
            $candidato['motivos'] = $motivos;
            $avaliados[] = $candidato;
        }

        usort($avaliados, static function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            if ($a['oficial'] !== $b['oficial']) {
                return $b['oficial'] <=> $a['oficial'];
            }

            return str_word_count((string) $b['nome']) <=> str_word_count((string) $a['nome']);
        });

        return $avaliados;
    }

    /**
     * Melhor candidato acima do limiar, ou null para manter a imagem atual.
     *
     * @param list<array<string, mixed>> $candidatos
     * @param array<string, mixed>       $perfil
     * @param array<string, mixed>       $config
     * @return array<string, mixed>|null
     */
    public static function escolher(array $candidatos, array $perfil, array $config): ?array
    {
        $minimo = (float) ($config['score_minimo'] ?? 8.0);
        $avaliados = self::avaliarCandidatos($candidatos, $perfil, $config);

        foreach ($avaliados as $candidato) {
            if ((float) $candidato['score'] >= $minimo) {
                return $candidato;
            }
        }

        return null;
    }

    /**
     * Perfil de busca de uma pergunta: filme, ano e palavras-chave do eixo.
     *
     * @param array<string, mixed> $pergunta
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function perfil(array $pergunta, array $config): array
    {
        $meta = $pergunta['midia_meta'] ?? [];

        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }

        $meta = is_array($meta) ? $meta : [];
        $legenda = trim((string) ($meta['legenda'] ?? ''));
        $titulo = trim((string) preg_replace('/\s*\(\d{4}\)\s*$/', '', $legenda));
        $chave = (string) ($pergunta['texto_key'] ?? '');
        $poster = '';

        if (($pergunta['midia_tipo'] ?? null) === 'imagem') {
            $poster = trim((string) ($pergunta['midia_url_ou_id'] ?? ''));
        } elseif (isset($meta['poster']) && is_string($meta['poster'])) {
            $poster = trim($meta['poster']);
        }

        return [
            'titulo'         => $titulo,
            'ano'            => isset($meta['ano']) && is_numeric($meta['ano']) ? (int) $meta['ano'] : null,
            'legenda'        => $legenda !== '' ? $legenda : $titulo,
            'poster'         => $poster,
            'tmdb_id'        => isset($pergunta['tmdb_id']) && $pergunta['tmdb_id'] !== null
                ? (int) $pergunta['tmdb_id']
                : (isset($meta['tmdb_id']) ? (int) $meta['tmdb_id'] : null),
            'palavras_chave' => $config['palavras_chave'][$chave] ?? [],
        ];
    }

    /**
     * Metadados de `midia_meta` para um clipe escolhido.
     *
     * @param array<string, mixed> $escolha
     * @param array<string, mixed> $perfil
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function meta(array $escolha, array $perfil, array $config): array
    {
        $trecho = self::trecho(
            isset($escolha['duracao']) && is_numeric($escolha['duracao']) ? (int) $escolha['duracao'] : null,
            (int) ($config['duracao_padrao'] ?? 12)
        );

        $legenda = trim((string) ($perfil['legenda'] ?? ''));

        if ($legenda === '') {
            $legenda = trim((string) ($perfil['titulo'] ?? ''));
        }

        $meta = [
            'legenda'       => $legenda,
            'ano'           => $perfil['ano'] ?? null,
            'start'         => $trecho['start'],
            'end'           => $trecho['end'],
            'fonte'         => 'tmdb',
            'tmdb_id'       => $perfil['tmdb_id'] ?? null,
            'video_nome'    => (string) ($escolha['nome'] ?? ''),
            'video_tipo'    => (string) ($escolha['tipo'] ?? 'Clip'),
            'video_idioma'  => (string) ($escolha['idioma'] ?? ''),
            'video_score'   => round((float) ($escolha['score'] ?? 0), 1),
            'credito'       => 'YouTube',
        ];

        if (!empty($perfil['poster'])) {
            $meta['poster'] = (string) $perfil['poster'];
        }

        return $meta;
    }

    /**
     * Janela curta do loop. Sem duração, usa o padrão; com duração, evita
     * intros muito longas e mantém o clipe curto.
     *
     * @return array{start: int, end: int}
     */
    public static function trecho(?int $duracao = null, int $padrao = 12): array
    {
        $padrao = max(4, min(30, $padrao));

        if ($duracao === null || $duracao <= 0) {
            return ['start' => 0, 'end' => $padrao];
        }

        if ($duracao <= $padrao + 2) {
            return ['start' => 0, 'end' => max(4, $duracao)];
        }

        $inicio = $duracao > 60 ? min(20, (int) floor($duracao * 0.1)) : 0;

        return ['start' => $inicio, 'end' => $inicio + $padrao];
    }

    /** @param array<string, mixed> $config @return array<string, float> */
    private static function pesos(array $config): array
    {
        $padrao = [
            'oficial'       => 5.0,
            'palavra_chave' => 8.0,
            'max_palavras'  => 24.0,
            'titulo'        => 3.0,
            'idioma_pt'     => 2.0,
            'idioma_en'     => 1.0,
            'generico'      => -5.0,
        ];

        $recebidos = is_array($config['pesos'] ?? null) ? $config['pesos'] : [];

        foreach ($padrao as $chave => $valor) {
            if (isset($recebidos[$chave]) && is_numeric($recebidos[$chave])) {
                $padrao[$chave] = (float) $recebidos[$chave];
            }
        }

        return $padrao;
    }

    /** @return list<string> */
    private static function lista(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_filter($valor, 'is_string'));
    }

    /** @param list<string> $lista */
    private static function contemAlguma(string $texto, array $lista): bool
    {
        foreach ($lista as $item) {
            if ($item !== '' && str_contains($texto, $item)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $genericas */
    private static function ehGenerico(string $nome, array $genericas): bool
    {
        if (in_array($nome, $genericas, true)) {
            return true;
        }

        return str_starts_with($nome, 'clip starring') || str_starts_with($nome, 'clip with');
    }

    private static function normalizar(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? '';

        return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    }
}
