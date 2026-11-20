<?php

declare(strict_types=1);

use Core\Translator;

/**
 * Helpers globais usados nas views. Mantidos pequenos e sem estado.
 */

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Caminho absoluto para um asset em public/assets, com cache-busting. */
function asset(string $caminho): string
{
    $caminho = ltrim($caminho, '/');
    $arquivo = BASE_PATH . '/public/assets/' . $caminho;
    $versao  = is_file($arquivo) ? (string) filemtime($arquivo) : '1';

    return '/assets/' . $caminho . '?v=' . $versao;
}

/**
 * URL da aplicação. Aceita pares chave/valor para a query string.
 *
 * @param array<string, string|int|null> $parametros
 */
function url(string $caminho = '', array $parametros = []): string
{
    $url = '/' . ltrim($caminho, '/');
    $parametros = array_filter(
        $parametros,
        static fn (mixed $v): bool => $v !== null && $v !== ''
    );

    if ($parametros !== []) {
        $url .= '?' . http_build_query($parametros);
    }

    return $url;
}

/** @param array<string, string|int|float> $substituicoes */
function t(string $chave, array $substituicoes = []): string
{
    return Translator::get($chave, $substituicoes);
}

/**
 * Lista/estrutura aninhada do arquivo de tradução.
 *
 * @return array<mixed>
 */
function tl(string $chave): array
{
    return Translator::lista($chave);
}

/** Número no padrão brasileiro. */
function numero(float|int $valor, int $decimais = 1): string
{
    return number_format((float) $valor, $decimais, ',', '.');
}

/** Percentual curto: 72% / 72,4%. */
function pct(float|int $valor, int $decimais = 0): string
{
    return numero($valor, $decimais) . '%';
}

/** Número para CSS (ponto decimal, sem zeros à direita). */
function num_css(float|int $valor): string
{
    $texto = rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');

    return $texto === '' ? '0' : $texto;
}

/**
 * URL absoluta de uma imagem/pôster a partir do caminho da TMDB.
 * O catálogo guarda apenas o caminho (ex.: /abc.jpg), nunca a URL completa.
 */
function tmdb_img(?string $caminho, string $tamanho = 'w342'): string
{
    $caminho = trim((string) $caminho);

    if ($caminho === '') {
        return '';
    }

    if (str_starts_with($caminho, 'https://') || str_starts_with($caminho, '/assets/')) {
        return $caminho;
    }

    return 'https://image.tmdb.org/t/p/' . trim($tamanho, '/') . '/' . ltrim($caminho, '/');
}

/** URL de logo de plataforma a partir do caminho da TMDB. */
function tmdb_logo(?string $caminho): string
{
    return tmdb_img($caminho, 'w92');
}

/** URL de imagem TMDB via proxy same-origin (para html2canvas). */
function img_proxy(?string $url): string
{
    $url = (string) $url;

    if ($url === '' || !str_starts_with($url, 'https://image.tmdb.org/')) {
        return $url;
    }

    return url('api/imagem.php', ['u' => $url]);
}

/** JSON seguro para atributo data-*. */
function json_attr(mixed $dados): string
{
    return htmlspecialchars(
        json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Cena ilustrativa de uma pergunta, já resolvida para o front-end.
 *
 * @param array<string, mixed> $pergunta linha de `perguntas`
 * @return array<string, mixed>|null
 */
function midia_pergunta(array $pergunta, string $tamanho = 'w780'): ?array
{
    return \Services\MidiaService::resolver(
        isset($pergunta['midia_tipo']) ? (string) $pergunta['midia_tipo'] : null,
        isset($pergunta['midia_url_ou_id']) ? (string) $pergunta['midia_url_ou_id'] : null,
        $pergunta['midia_meta'] ?? null,
        $tamanho
    );
}

/** Iniciais para o monograma dos cards sem imagem. */
function iniciais(string $nome): string
{
    $limpo = preg_replace('/\([^)]*\)/', '', $nome) ?? $nome;
    $partes = preg_split('/\s+/', trim($limpo)) ?: [];
    $ignorar = ['o', 'a', 'os', 'as', 'de', 'da', 'do', 'das', 'dos', 'e', 'the', 'of'];
    $partes = array_values(array_filter(
        $partes,
        static fn (string $p): bool => $p !== '' && !in_array(mb_strtolower($p), $ignorar, true)
    ));

    if ($partes === []) {
        return '?';
    }

    $letras = mb_substr($partes[0], 0, 1);

    if (count($partes) > 1) {
        $letras .= mb_substr($partes[count($partes) - 1], 0, 1);
    }

    return mb_strtoupper($letras);
}

/**
 * Ícones em SVG inline (traço de 24x24). Evita dependência de fonte de ícones
 * e mantém a demo funcionando offline.
 */
function icon(string $nome, int $tamanho = 20, string $classe = ''): string
{
    static $mapa = [
        'frame'      => 'M8 4v11a1 1 0 0 0 1 1h11M4 8h11a1 1 0 0 1 1 1v11',
        'sparkles'   => 'M12 3l1.8 4.9L19 10l-5.2 2.1L12 17l-1.8-4.9L5 10l5.2-2.1L12 3z|M19 15.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7.7-1.8z',
        'moon'       => 'M12 3h.4a7.5 7.5 0 0 0 7.9 12.4A9 9 0 1 1 12 3z',
        'sun'        => 'M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0-8z|M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4',
        'building'   => 'M3 21h18M5 21V7l7-4l7 4v14M9 9h1M9 13h1M9 17h1M14 9h1M14 13h1M14 17h1',
        'planet'     => 'M12 5a7 7 0 1 0 0 14a7 7 0 0 0 0-14z|M4.5 17c3.5 1 9-1.5 12-5s3.5-7 1.5-7.5|M19.5 7c-3.5-1-9 1.5-12 5s-3.5 7-1.5 7.5',
        'feather'    => 'M13 10L5 18M13 5a5.5 5.5 0 0 1 6 6c-2 5-8 8-14 8c0-6 3-12 8-14z',
        'bolt'       => 'M13 3L5 14h5l-1 7l8-11h-5l1-7z',
        'scale'      => 'M12 3v18M7 21h10M6 7l6-1l6 1M3 21l3-11l3 11M15 21l3-11l3 11',
        'shield'     => 'M12 3l7 3v5.5c0 4-3 7-7 9.5c-4-2.5-7-5.5-7-9.5V6l7-3z',
        'list'       => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
        'layers'     => 'M12 3l9 5l-9 5l-9-5l9-5zM3 13l9 5l9-5M3 17l9 5l9-5',
        'home'       => 'M5 12H3l9-9l9 9h-2M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7M9 21v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6',
        'mountain'   => 'M3 20h18L14 6l-4 7l-2-3l-5 10z',
        'film'       => 'M4 4h16v16H4zM4 9h16M4 15h16M9 4v16M15 4v16',
        'rocket'     => 'M12 3c3 2 5 5 5 9l-3 3H10l-3-3c0-4 2-7 5-9zM9 15l-2 5l4-2M15 15l2 5l-4-2M12 8a1 1 0 1 0 0 2a1 1 0 0 0 0-2',
        'arrow-right'=> 'M5 12h14M13 6l6 6-6 6',
        'arrow-left' => 'M19 12H5M11 6l-6 6l6 6',
        'check'      => 'M5 12l5 5L20 7',
        'x'          => 'M6 6l12 12M18 6L6 18',
        'minus'      => 'M6 12h12',
        'chevron'    => 'M6 9l6 6l6-6',
        'clock'      => 'M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0-18zM12 7v5l3 2',
        'pin'        => 'M12 11a3 3 0 1 0 0-6a3 3 0 0 0 0 6z|M17.7 14.6c1.5 1.6 2.3 3.1 2.3 4.4c0 2.4-3.6 4-8 4s-8-1.6-8-4c0-1.3.8-2.8 2.3-4.4',
        'star'       => 'M12 3l2.8 5.7l6.2.9l-4.5 4.4l1 6.2L12 17.3l-5.5 2.9l1-6.2L3 9.6l6.2-.9L12 3z',
        'users'      => 'M9 7a3 3 0 1 0 6 0a3 3 0 0 0-6 0zM3 21v-1a6 6 0 0 1 6-6h6a6 6 0 0 1 6 6v1M16 3.5a3 3 0 0 1 0 7',
        'copy'       => 'M8 8h10v12H8zM6 16H4V4h12v2',
        'share'      => 'M12 3v12M7 7l5-4l5 4M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5',
        'download'   => 'M12 3v12M7 10l5 5l5-5M5 21h14',
        'compass'    => 'M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0-18zM12 12l3-6l-6 3l-3 6l6-3z',
        'refresh'    => 'M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5',
        'heart'      => 'M19.5 12.6L12 20l-7.5-7.4a5 5 0 0 1 7.1-7.1l.4.4l.4-.4a5 5 0 0 1 7.1 7.1z',
        'bookmark-plus'  => 'M6 3h12a1 1 0 0 1 1 1v17l-7-4l-7 4V4a1 1 0 0 1 1-1z|M12 8v6M9 11h6',
        'bookmark-check' => 'M6 3h12a1 1 0 0 1 1 1v17l-7-4l-7 4V4a1 1 0 0 1 1-1z|M9 10l2 2l4-4',
        'eye'        => 'M2 12s3.5-6 10-6s10 6 10 6s-3.5 6-10 6S2 12 2 12z|M12 9a3 3 0 1 0 0 6a3 3 0 0 0 0-6z',
        'search'     => 'M11 4a7 7 0 1 0 0 14a7 7 0 0 0 0-14z|M16 16l5 5',
        'lock'       => 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4',
        'globe'      => 'M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0-18zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 0 0 0 18M12.5 3a17 17 0 0 1 0 18',
        'external'   => 'M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4',
        'grid'       => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'play'       => 'M8 5l11 7l-11 7l0-14z',
        'quote'      => 'M9 7H5v6h4v4H5M19 7h-4v6h4v4h-4',
    ];

    $caminhos = $mapa[$nome] ?? $mapa['spark'];

    $svg = sprintf(
        '<svg class="pc-ico %s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">',
        e($classe),
        $tamanho,
        $tamanho
    );

    foreach (explode('|', $caminhos) as $d) {
        $svg .= '<path d="' . e($d) . '"/>';
    }

    return $svg . '</svg>';
}

/** Nome legível do eixo (banco tem prioridade; lang é fallback). */
function eixo_nome(array $eixo): string
{
    $nome = trim((string) ($eixo['nome'] ?? ''));

    return $nome !== '' ? $nome : t('eixo.' . ($eixo['slug'] ?? '') . '.nome');
}

/** Descrição do eixo, com fallback para o arquivo de tradução. */
function eixo_descricao(array $eixo): string
{
    $descricao = trim((string) ($eixo['descricao'] ?? ''));

    return $descricao !== '' ? $descricao : t('eixo.' . ($eixo['slug'] ?? '') . '.desc');
}

/** Nome do polo esquerdo ('esq') ou direito ('dir'). */
function eixo_polo(array $eixo, string $lado): string
{
    $lado = $lado === 'dir' ? 'dir' : 'esq';
    $nome = trim((string) ($eixo['polo_' . $lado . '_nome'] ?? ''));

    if ($nome !== '') {
        return $nome;
    }

    return t((string) ($eixo['polo_' . $lado . '_key'] ?? ''));
}

/** Origem curta a partir do local de nascimento da TMDB. */
function origem_curta(?string $pais): string
{
    $pais = trim((string) $pais);

    if ($pais === '') {
        return '';
    }

    $partes = array_values(array_filter(array_map('trim', explode(',', $pais))));
    $ultimo = $partes !== [] ? (string) end($partes) : $pais;

    $mapa = [
        'USA' => 'EUA', 'U.S.A.' => 'EUA', 'United States' => 'EUA',
        'United States of America' => 'EUA', 'UK' => 'Reino Unido',
        'England' => 'Reino Unido', 'Scotland' => 'Reino Unido', 'Wales' => 'Reino Unido',
        'France' => 'França', 'Italy' => 'Itália', 'Spain' => 'Espanha',
        'Germany' => 'Alemanha', 'Japan' => 'Japão', 'Canada' => 'Canadá',
        'Brazil' => 'Brasil', 'Mexico' => 'México', 'Australia' => 'Austrália',
        'Ireland' => 'Irlanda', 'New Zealand' => 'Nova Zelândia', 'Sweden' => 'Suécia',
        'Denmark' => 'Dinamarca', 'Norway' => 'Noruega', 'Poland' => 'Polônia',
        'China' => 'China', 'Hong Kong' => 'Hong Kong', 'South Korea' => 'Coreia do Sul',
        'Argentina' => 'Argentina', 'Portugal' => 'Portugal', 'Netherlands' => 'Países Baixos',
        'Belgium' => 'Bélgica', 'Switzerland' => 'Suíça', 'Austria' => 'Áustria',
        'Russia' => 'Rússia', 'India' => 'Índia', 'Israel' => 'Israel',
        'South Africa' => 'África do Sul', 'Czech Republic' => 'Chéquia',
        'Lebanon' => 'Líbano', 'Turkey' => 'Turquia', 'Greece' => 'Grécia',
        'Finland' => 'Finlândia', 'Iceland' => 'Islândia', 'Chile' => 'Chile',
        'Colombia' => 'Colômbia', 'Peru' => 'Peru', 'Uruguay' => 'Uruguai',
    ];

    return $mapa[$ultimo] ?? $ultimo;
}

/** Rótulo de intensidade de um eixo (Equilibrado / Tendência / Purista). */
function intensidade(float $percentual): string
{
    $distancia = abs($percentual - 50.0);

    if ($distancia < 8) {
        return 'equilibrado';
    }

    return $distancia < 20 ? 'tendencia' : 'purista';
}

/**
 * Cor do eixo mais extremo de um vetor (usada nos monogramas e pôsteres).
 *
 * @param list<float> $vetor
 * @param list<array<string, mixed>> $eixos
 */
function cor_dominante(array $vetor, array $eixos): string
{
    $maiorDistancia = -1.0;
    $cor = '#0c3b2e';

    foreach (array_values($eixos) as $indice => $eixo) {
        $percentual = (float) ($vetor[$indice] ?? 50);
        $distancia = abs($percentual - 50.0);

        if ($distancia > $maiorDistancia) {
            $maiorDistancia = $distancia;
            $cor = $percentual >= 50 ? (string) $eixo['cor_dir'] : (string) $eixo['cor_esq'];
        }
    }

    return $cor;
}

/** Rótulo do modo de teste usado no resultado e no admin. */
function modo_rotulo(string $modo): string
{
    return t(match ($modo) {
        'rapido'  => 'resultado.mode_quick',
        'extremo' => 'resultado.mode_extreme',
        default   => 'resultado.mode_full',
    });
}

/**
 * Polos em que o vetor de uma entidade é mais forte.
 *
 * @param array<string, mixed> $entidade linha com `vetor`
 * @param list<array<string, mixed>> $eixos
 * @return list<array{eixo: string, polo: string, pct: float, cor: string}>
 */
function entidade_polos(array $entidade, array $eixos, int $limite = 2): array
{
    $itens = [];

    foreach (array_values($eixos) as $indice => $eixo) {
        $pct = (float) (($entidade['vetor'] ?? [])[$indice] ?? 50);
        $direita = $pct >= 50;

        $itens[] = [
            'distancia' => abs($pct - 50),
            'eixo'      => eixo_nome($eixo),
            'polo'      => eixo_polo($eixo, $direita ? 'dir' : 'esq'),
            'pct'       => round($direita ? $pct : 100 - $pct, 0),
            'cor'       => (string) ($direita ? $eixo['cor_dir'] : $eixo['cor_esq']),
        ];
    }

    usort($itens, static fn (array $a, array $b): int => $b['distancia'] <=> $a['distancia']);

    return array_map(
        static fn (array $item): array => [
            'eixo' => $item['eixo'],
            'polo' => $item['polo'],
            'pct'  => $item['pct'],
            'cor'  => $item['cor'],
        ],
        array_slice($itens, 0, max(1, $limite))
    );
}

/** Ano de fundação/estreia legível ("2012" ou "" quando não há). */
function ano_curto(mixed $ano): string
{
    return is_numeric($ano) && (int) $ano >= 1800 ? (string) (int) $ano : '';
}

/**
 * Nome curto para o card de compartilhamento: remove o alias entre parênteses
 * ("Batman (Bruce Wayne)" → "Batman") e mantém no máximo duas palavras.
 */
function nome_curto(string $nome): string
{
    $nome = preg_replace('/\s*\([^)]*\)\s*/u', ' ', trim($nome)) ?? trim($nome);
    $partes = preg_split('/\s+/u', trim($nome)) ?: [];
    $partes = array_values(array_filter($partes, static fn (string $p): bool => $p !== ''));

    return implode(' ', array_slice($partes, 0, 2));
}
