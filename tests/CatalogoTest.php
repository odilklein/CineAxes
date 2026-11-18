<?php

declare(strict_types=1);

use Models\Decada;
use Models\FilmeCard;
use Services\GroqService;
use Services\RetratoService;

require_once __DIR__ . '/_assert.php';

teste('card de filme encontra o pôster pelo título e ano', function (): void {
    $card = FilmeCard::card('Forrest Gump', 1994);

    afirmar($card['tmdb'] !== null, 'esperava um id da TMDB');
    afirmar(is_string($card['poster']) && str_contains($card['poster'], 'image.tmdb.org'), 'esperava pôster');
    igual(1994, $card['ano']);
});

teste('card de filme aceita título do catálogo com subtítulo', function (): void {
    $card = FilmeCard::card('Pulp Fiction: Tempo de Violência', 1994);

    afirmar($card['tmdb'] !== null, 'o fallback por dois-pontos deveria resolver');
});

teste('filme fora do mapa não quebra o card', function (): void {
    $card = FilmeCard::card('Filme Inexistente XYZ', 1999);

    igual('Filme Inexistente XYZ', $card['titulo']);
    igual(1999, $card['ano']);
    igual(null, $card['tmdb']);
    igual(null, $card['poster']);
});

teste('lista de filmes é enriquecida sem perder os campos originais', function (): void {
    $cards = FilmeCard::enriquecer([
        ['titulo' => 'Matrix', 'ano' => 1999, 'origem' => 'Neo'],
    ]);

    igual(1, count($cards));
    igual('Neo', $cards[0]['origem']);
    afirmar($cards[0]['tmdb'] !== null, 'esperava id da TMDB');
});

teste('cada década tem epíteto, curiosidade e três filmes-marco', function (): void {
    $decadas = Decada::todas();

    afirmar(count($decadas) >= 13, 'esperava pelo menos 13 décadas');

    foreach ($decadas as $ano => $decada) {
        afirmar(trim((string) ($decada['epiteto'] ?? '')) !== '', $ano . ': epíteto vazio');
        afirmar(trim((string) ($decada['curiosidade'] ?? '')) !== '', $ano . ': curiosidade vazia');
        igual(3, count($decada['marcos'] ?? []), $ano . ': esperava três marcos');
    }
});

teste('década fora do mapa é limitada à primeira', function (): void {
    $decada = Decada::porAno(1850);

    igual('O cinema aprende a contar', $decada['epiteto']);
});

teste('década arredonda o ano para baixo', function (): void {
    $decada = Decada::porAno(1977);

    igual('O New Hollywood', $decada['epiteto']);
});

teste('retrato separa a frase do texto', function (): void {
    $servico = new RetratoService(new GroqService([]));
    [$frase, $texto] = $servico->separar("FRASE: Você assiste como quem monta um mapa.\nTEXTO:\nPrimeiro parágrafo. Segundo parágrafo. Terceiro.");

    igual('Você assiste como quem monta um mapa.', $frase);
    afirmar(str_starts_with($texto, 'Primeiro parágrafo.'), 'o texto deveria começar depois de TEXTO:');
});

teste('retrato sem o formato esperado mantém o texto inteiro', function (): void {
    $servico = new RetratoService(new GroqService([]));
    [$frase, $texto] = $servico->separar('Só um texto corrido, sem marcadores.');

    igual(null, $frase);
    igual('Só um texto corrido, sem marcadores.', $texto);
});
