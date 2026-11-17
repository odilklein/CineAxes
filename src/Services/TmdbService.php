<?php

declare(strict_types=1);

namespace Services;

use PDO;
use Throwable;

/**
 * Cliente da API v3 da TMDB para enriquecer o catálogo do PersonaCut.
 *
 * - Requisições somente no servidor (a chave nunca chega ao navegador).
 * - Cache em MySQL (cache_tmdb) com TTL de 24h.
 * - Aceita chave v3 (32 caracteres) ou token v4 (Bearer/JWT).
 * - Falha de rede/API nunca lança exceção: devolve null/[].
 */
final class TmdbService
{
    private string $apiKey;
    private string $baseUrl;
    private string $imageBase;
    private string $language;
    private string $region;
    private int $ttl;
    private PDO $pdo;
    private int $ultimoStatus = 0;

    /** @param array<string, mixed> $config */
    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->apiKey = trim((string) ($config['api_key'] ?? ''));
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.themoviedb.org/3'), '/');
        $this->imageBase = rtrim((string) ($config['image_url'] ?? 'https://image.tmdb.org/t/p'), '/');
        $this->language = (string) ($config['language'] ?? 'pt-BR');
        $this->region = (string) ($config['region'] ?? 'BR');
        $this->ttl = max(60, (int) ($config['cache_ttl'] ?? 86400));
    }

    public function disponivel(): bool
    {
        return $this->apiKey !== '';
    }

    public function ultimoStatus(): int
    {
        return $this->ultimoStatus;
    }

    /** URL absoluta de uma imagem da TMDB. */
    public function imagem(?string $path, string $tamanho = 'w500'): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return $this->imageBase . '/' . trim($tamanho, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Busca um filme pelo título e ano. Prefere correspondência exata de título.
     *
     * @return array<string, mixed>|null
     */
    public function procurarFilme(string $titulo, ?int $ano = null): ?array
    {
        $params = [
            'query' => $titulo,
            'include_adult' => 'false',
        ];

        if ($ano !== null && $ano > 0) {
            $params['year'] = $ano;
        }

        $resposta = $this->buscar('/search/movie', $params);

        if ($resposta === null || empty($resposta['results'])) {
            return null;
        }

        $alvo = $this->normalizar($titulo);
        $melhor = null;
        $melhorNota = -1;

        foreach ($resposta['results'] as $candidato) {
            $nota = 0;
            $nome = $this->normalizar((string) ($candidato['title'] ?? ''));
            $original = $this->normalizar((string) ($candidato['original_title'] ?? ''));
            $data = (string) ($candidato['release_date'] ?? '');
            $anoCandidato = $data !== '' ? (int) substr($data, 0, 4) : 0;

            if ($nome === $alvo || $original === $alvo) {
                $nota += 10;
            } elseif (str_contains($nome, $alvo) || str_contains($alvo, $nome)) {
                $nota += 4;
            }

            if ($ano !== null && $anoCandidato === $ano) {
                $nota += 6;
            } elseif ($ano !== null && $anoCandidato > 0 && abs($anoCandidato - $ano) <= 1) {
                $nota += 2;
            }

            $nota += min(3, (int) round(log10(max(1, (int) ($candidato['popularity'] ?? 0)) + 1)));

            if ($nota > $melhorNota) {
                $melhorNota = $nota;
                $melhor = $candidato;
            }
        }

        return $melhor;
    }

    /** @return array<string, mixed>|null */
    public function procurarPessoa(string $nome): ?array
    {
        $resposta = $this->buscar('/search/person', ['query' => $nome]);

        if ($resposta === null || empty($resposta['results'])) {
            return null;
        }

        $alvo = $this->normalizar($nome);

        foreach ($resposta['results'] as $candidato) {
            if ($this->normalizar((string) ($candidato['name'] ?? '')) === $alvo) {
                return $candidato;
            }
        }

        return $resposta['results'][0];
    }

    /** @return array<string, mixed>|null */
    public function procurarEmpresa(string $nome): ?array
    {
        $resposta = $this->buscar('/search/company', ['query' => $nome]);

        if ($resposta === null || empty($resposta['results'])) {
            return null;
        }

        $alvo = $this->normalizar($nome);

        foreach ($resposta['results'] as $candidato) {
            if ($this->normalizar((string) ($candidato['name'] ?? '')) === $alvo) {
                return $candidato;
            }
        }

        return $resposta['results'][0];
    }

    /** @return array<string, mixed>|null */
    public function filme(int $id): ?array
    {
        return $this->buscar('/movie/' . $id);
    }

    /** Detalhes + elenco em uma única chamada (usado pelo Quick View). */
    public function filmeComCreditos(int $id): ?array
    {
        return $this->buscar('/movie/' . $id, ['append_to_response' => 'credits']);
    }

    /** @return array<string, mixed>|null */
    public function pessoa(int $id): ?array
    {
        return $this->buscar('/person/' . $id);
    }

    /** @return array<string, mixed>|null */
    public function empresa(int $id): ?array
    {
        return $this->buscar('/company/' . $id);
    }

    /** @return array<int, array<string, mixed>> */
    public function logosEmpresa(int $id): array
    {
        $resposta = $this->buscar('/company/' . $id . '/images');

        return is_array($resposta['logos'] ?? null) ? $resposta['logos'] : [];
    }

    /**
     * Traileres, teasers e clipes de um filme.
     *
     * O idioma padrão é en-US porque é onde a TMDB concentra os clipes
     * oficiais de cena; a versão pt-BR costuma trazer só trailers dublados.
     *
     * @return list<array<string, mixed>>
     */
    public function videos(int $tmdbId, string $language = 'en-US'): array
    {
        if ($tmdbId <= 0) {
            return [];
        }

        $resposta = $this->buscar('/movie/' . $tmdbId . '/videos', ['language' => $language]);
        $resultados = $resposta['results'] ?? null;

        if (!is_array($resultados)) {
            return [];
        }

        return array_values(array_filter($resultados, 'is_array'));
    }

    /* ------------------------------ HTTP/Cache ----------------------------- */

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function buscar(string $caminho, array $params = []): ?array
    {
        if (!$this->disponivel()) {
            return null;
        }

        $params = array_filter(
            $params,
            static fn (mixed $v): bool => $v !== null && $v !== ''
        );

        if (!isset($params['language'])) {
            $params['language'] = $this->language;
        }

        ksort($params);

        $chave = hash('sha256', $caminho . '?' . http_build_query($params));
        $cache = $this->cacheLer($chave);

        if ($cache !== null) {
            return $cache;
        }

        $payload = $this->requisitar($caminho, $params);

        if ($payload !== null) {
            $this->cacheGravar($chave, $payload);
        }

        return $payload;
    }

    /** @param array<string, mixed> $params @return array<string, mixed>|null */
    private function requisitar(string $caminho, array $params): ?array
    {
        $headers = ['Accept: application/json'];

        if ($this->usaBearer()) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        } else {
            $params['api_key'] = $this->apiKey;
        }

        $url = $this->baseUrl . $caminho . '?' . http_build_query($params);

        $curl = curl_init($url);

        if ($curl === false) {
            return null;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'PersonaCut/1.0',
        ]);

        $corpo = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($curl);
        curl_close($curl);

        $this->ultimoStatus = $status;

        if ($corpo === false || $status !== 200) {
            error_log(sprintf(
                '[PersonaCut][tmdb] %s em %s (HTTP %d)',
                $erro !== '' ? $erro : 'requisição falhou',
                $caminho,
                $status
            ));

            return null;
        }

        $dados = json_decode($corpo, true);

        return is_array($dados) ? $dados : null;
    }

    private function usaBearer(): bool
    {
        return strlen($this->apiKey) > 80 && str_contains($this->apiKey, '.');
    }

    /** @return array<string, mixed>|null */
    private function cacheLer(string $chave): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT payload_json FROM cache_tmdb
                  WHERE cache_key = :chave AND expires_at > NOW()
                  LIMIT 1'
            );
            $stmt->execute(['chave' => $chave]);
            $payload = $stmt->fetchColumn();

            if ($payload === false || !is_string($payload)) {
                return null;
            }

            $dados = json_decode($payload, true);

            return is_array($dados) ? $dados : null;
        } catch (Throwable $erro) {
            return null;
        }
    }

    /** @param array<string, mixed> $payload */
    private function cacheGravar(string $chave, array $payload): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO cache_tmdb (cache_key, payload_json, expires_at)
                 VALUES (:chave, :payload, :expira)
                 ON DUPLICATE KEY UPDATE payload_json = VALUES(payload_json),
                                         expires_at = VALUES(expires_at),
                                         created_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([
                'chave' => $chave,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'expira' => date('Y-m-d H:i:s', time() + $this->ttl),
            ]);
        } catch (Throwable $erro) {
            // Sem cache o enriquecimento continua, só mais lento.
        }
    }

    private function normalizar(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? '';

        return trim($texto);
    }
}
