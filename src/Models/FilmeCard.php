<?php

declare(strict_types=1);

namespace Models;

/**
 * Card de filme citado no perfil (pôster + TMDB quando disponível).
 *
 * O mapa é gerado por bin/filmes_cards.php e versionado em
 * config/filmes_cards.php: a página de resultado não faz chamadas de rede para
 * montar os cards. Filme fora do mapa continua com card tipográfico.
 */
final class FilmeCard
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $cache = null;

    /** @return array<string, array<string, mixed>> */
    public static function todos(): array
    {
        if (self::$cache === null) {
            $arquivo = dirname(__DIR__, 2) . '/config/filmes_cards.php';
            /** @var array<string, array<string, mixed>> $mapa */
            $mapa = is_file($arquivo) ? require $arquivo : [];
            self::$cache = $mapa;
        }

        return self::$cache;
    }

    /**
     * Card pronto para a view: título, ano, id TMDB e pôster.
     *
     * @return array{titulo: string, ano: int|null, tmdb: int|null, poster: string|null}
     */
    public static function card(string $titulo, mixed $ano = null): array
    {
        $titulo = trim($titulo);
        $anoInt = is_numeric($ano) ? (int) $ano : null;
        $mapa = self::todos();
        $chave = mb_strtolower($titulo) . '|' . ($anoInt ?? '');

        $item = $mapa[$chave] ?? null;

        // Títulos do catálogo trazem subtítulo ("Pulp Fiction: Tempo de
        // Violência") e o mapa pode ter só a parte principal.
        if ($item === null && str_contains($titulo, ':')) {
            $curto = mb_strtolower(trim(explode(':', $titulo)[0]));
            $item = $mapa[$curto . '|' . ($anoInt ?? '')] ?? null;
        }

        return [
            'titulo' => $titulo,
            'ano'    => $anoInt ?? (isset($item['ano']) ? (int) $item['ano'] : null),
            'tmdb'   => isset($item['tmdb']) && (int) $item['tmdb'] > 0 ? (int) $item['tmdb'] : null,
            'poster' => isset($item['poster']) && is_string($item['poster']) && $item['poster'] !== ''
                ? $item['poster']
                : null,
        ];
    }

    /**
     * Enriquece uma lista de filmes (['titulo' => ..., 'ano' => ...]) com o card.
     *
     * @param list<array<string, mixed>> $filmes
     * @return list<array<string, mixed>>
     */
    public static function enriquecer(array $filmes): array
    {
        $cards = [];

        foreach ($filmes as $filme) {
            $card = self::card((string) ($filme['titulo'] ?? ''), $filme['ano'] ?? null);
            $extras = array_filter(
                $card,
                static fn (mixed $valor): bool => $valor !== null && $valor !== ''
            );

            $cards[] = array_merge($filme, $extras);
        }

        return $cards;
    }
}
