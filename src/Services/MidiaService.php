<?php

declare(strict_types=1);

namespace Services;

/**
 * Resolve a mídia ilustrativa de uma pergunta para o formato consumido
 * pelo front-end.
 *
 * Formatos aceitos:
 * - `imagem`: caminho TMDB (`/abc.jpg`), que vira uma URL de `image.tmdb.org`,
 *   ou uma URL absoluta (http/https).
 * - `gif`: URL absoluta de um GIF (GIPHY/Tenor), com poster opcional.
 * - `video_yt`: ID do YouTube ou URL (watch, youtu.be, embed, shorts), com
 *   `start`/`end` em segundos. O embed é montado sem som e em loop.
 *
 * A resolução é pura (não acessa banco nem rede), o que permite testá-la
 * sem subir o ambiente.
 */
final class MidiaService
{
    public const TIPOS = ['imagem', 'gif', 'video_yt'];

    private const YOUTUBE_EMBED = 'https://www.youtube-nocookie.com/embed/';
    private const TMDB_IMAGE = 'https://image.tmdb.org/t/p';

    /**
     * @param mixed $meta array, JSON string ou null
     * @return array{
     *     tipo: string,
     *     src?: string,
     *     poster?: string|null,
     *     video_id?: string,
     *     embed?: string,
     *     start?: int|null,
     *     end?: int|null,
     *     legenda: string,
     *     ano: int|null,
     *     credito: string|null
     * }|null
     */
    public static function resolver(
        ?string $tipo,
        ?string $urlOuId,
        mixed $meta = null,
        string $tamanho = 'w780',
        string $imageBase = self::TMDB_IMAGE
    ): ?array {
        $tipo = trim((string) $tipo);
        $valor = trim((string) $urlOuId);

        if (!in_array($tipo, self::TIPOS, true) || $valor === '') {
            return null;
        }

        $meta = self::metaArray($meta);
        $legenda = trim((string) ($meta['legenda'] ?? ''));
        $ano = isset($meta['ano']) && is_numeric($meta['ano']) && (int) $meta['ano'] > 0
            ? (int) $meta['ano']
            : null;
        $credito = trim((string) ($meta['credito'] ?? ''));
        $poster = self::imagemUrl((string) ($meta['poster'] ?? ''), $tamanho, $imageBase);

        $base = [
            'tipo'    => $tipo,
            'legenda' => $legenda,
            'ano'     => $ano,
            'credito' => $credito !== '' ? $credito : null,
        ];

        if ($tipo === 'imagem') {
            $src = self::imagemUrl($valor, $tamanho, $imageBase);

            if ($src === null) {
                return null;
            }

            if ($base['credito'] === null && self::ehCaminhoTmdb($valor)) {
                $base['credito'] = 'TMDB';
            }

            return $base + ['src' => $src];
        }

        if ($tipo === 'gif') {
            if (!self::ehUrl($valor)) {
                return null;
            }

            return $base + [
                'src'    => $valor,
                'poster' => $poster,
            ];
        }

        // video_yt
        $videoId = self::youtubeId($valor);

        if ($videoId === null) {
            return null;
        }

        $start = self::segundos($meta['start'] ?? null);
        $end = self::segundos($meta['end'] ?? null);

        if ($start !== null && $end !== null && $end <= $start) {
            $end = null;
        }

        $params = [
            'autoplay'       => '1',
            'mute'           => '1',
            'loop'           => '1',
            'playlist'       => $videoId,
            'controls'       => '0',
            'modestbranding' => '1',
            'rel'            => '0',
            'playsinline'    => '1',
        ];

        if ($start !== null) {
            $params['start'] = (string) $start;
        }

        if ($end !== null) {
            $params['end'] = (string) $end;
        }

        return $base + [
            'video_id' => $videoId,
            'embed'    => self::YOUTUBE_EMBED . $videoId . '?' . http_build_query($params),
            'poster'   => $poster ?? 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
            'start'    => $start,
            'end'      => $end,
        ];
    }

    /** Extrai o ID de 11 caracteres de uma URL/ID do YouTube. */
    public static function youtubeId(string $valor): ?string
    {
        $valor = trim($valor);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $valor) === 1) {
            return $valor;
        }

        $partes = parse_url($valor);

        if (!is_array($partes) || !isset($partes['host'])) {
            return null;
        }

        $host = strtolower((string) $partes['host']);
        $caminho = trim((string) ($partes['path'] ?? ''), '/');
        $id = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = explode('/', $caminho)[0] ?? null;
        } elseif (str_contains($host, 'youtube.com') || str_contains($host, 'youtube-nocookie.com')) {
            if ($caminho !== '' && preg_match('#^(?:embed|shorts|live)/([A-Za-z0-9_-]{11})#', $caminho, $m) === 1) {
                $id = $m[1];
            } else {
                parse_str((string) ($partes['query'] ?? ''), $query);
                $id = isset($query['v']) ? (string) $query['v'] : null;
            }
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1 ? $id : null;
    }

    /**
     * Normaliza a meta vinda do formulário administrativo.
     *
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public static function metaParaSalvar(array $dados, string $urlOuId): array
    {
        $meta = [];
        $legenda = trim((string) ($dados['legenda'] ?? ''));

        if ($legenda !== '') {
            $meta['legenda'] = mb_substr($legenda, 0, 120);
        }

        $ano = (int) ($dados['ano'] ?? 0);

        if ($ano >= 1888 && $ano <= 2100) {
            $meta['ano'] = $ano;
        }

        $start = self::segundos($dados['start'] ?? null);
        $end = self::segundos($dados['end'] ?? null);

        if ($start !== null) {
            $meta['start'] = $start;
        }

        if ($end !== null) {
            $meta['end'] = $end;
        }

        $meta['fonte'] = self::ehCaminhoTmdb($urlOuId) ? 'tmdb' : 'url';

        return $meta;
    }

    /** @return array<string, mixed> */
    private static function metaArray(mixed $meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (is_string($meta) && trim($meta) !== '') {
            $decodificada = json_decode($meta, true);

            return is_array($decodificada) ? $decodificada : [];
        }

        return [];
    }

    private static function imagemUrl(string $valor, string $tamanho, string $imageBase): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if (self::ehUrl($valor)) {
            return $valor;
        }

        if (self::ehCaminhoTmdb($valor)) {
            return rtrim($imageBase, '/') . '/' . trim($tamanho, '/') . '/' . ltrim($valor, '/');
        }

        return null;
    }

    private static function ehUrl(string $valor): bool
    {
        return preg_match('#^https?://#i', $valor) === 1;
    }

    private static function ehCaminhoTmdb(string $valor): bool
    {
        return str_starts_with($valor, '/') && !str_starts_with($valor, '//');
    }

    private static function segundos(mixed $valor): ?int
    {
        if (!is_numeric($valor)) {
            return null;
        }

        $segundos = (int) $valor;

        return $segundos > 0 ? $segundos : null;
    }
}
