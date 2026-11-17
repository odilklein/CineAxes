<?php

declare(strict_types=1);

namespace Core;

/**
 * Radar bipolar em SVG (sem Chart.js).
 *
 * O raio de cada eixo representa a posição de 0 a 100, então o desenho é sempre
 * coerente com as barras duplas. O anel tracejado marca o neutro (50).
 */
final class Radar
{
    /**
     * @param list<string> $rotulos
     * @param list<array{nome: string, valores: list<float|int>, cor: string}> $series
     * @param list<string> $coresPorEixo cores dos pontos do primeiro conjunto
     * @param string $idTitulo id único do <title> (a página pode ter mais de um radar)
     */
    public static function render(
        array $rotulos,
        array $series,
        array $coresPorEixo = [],
        string $idTitulo = 'radar-titulo'
    ): string {
        $total = count($rotulos);

        if ($total < 3) {
            return '';
        }

        $centro = 200.0;
        $raio = 132.0;
        $angulos = [];

        for ($i = 0; $i < $total; $i++) {
            $angulos[$i] = deg2rad(-90 + ($i * 360 / $total));
        }

        $ponto = static function (int $indice, float $fracao) use ($centro, $raio, $angulos): array {
            return [
                'x' => round($centro + $raio * $fracao * cos($angulos[$indice]), 2),
                'y' => round($centro + $raio * $fracao * sin($angulos[$indice]), 2),
            ];
        };

        $idTituloSeguro = htmlspecialchars($idTitulo, ENT_QUOTES, 'UTF-8');
        $svg = '<svg class="pc-radar-svg" viewBox="0 0 400 400" role="img" aria-labelledby="' . $idTituloSeguro . '">';
        $svg .= '<title id="' . $idTituloSeguro . '">Assinatura do perfil nos 8 eixos</title>';

        // Anéis
        foreach ([0.25, 0.5, 0.75, 1.0] as $fracao) {
            $pontos = [];

            for ($i = 0; $i < $total; $i++) {
                $p = $ponto($i, $fracao);
                $pontos[] = $p['x'] . ',' . $p['y'];
            }

            $neutro = $fracao === 0.5;
            $svg .= sprintf(
                '<polygon points="%s" class="pc-radar-anel%s"/>',
                implode(' ', $pontos),
                $neutro ? ' pc-radar-anel--neutro' : ''
            );
        }

        // Raios
        foreach ($angulos as $i => $angulo) {
            $p = $ponto($i, 1.0);
            $svg .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.2f" y2="%.2f" class="pc-radar-raio"/>', $centro, $centro, $p['x'], $p['y']);
        }

        // Rótulos
        foreach ($rotulos as $i => $rotulo) {
            $p = $ponto($i, 1.0);
            $cosseno = cos($angulos[$i]);
            $seno = sin($angulos[$i]);

            $x = $centro + ($raio + 20) * $cosseno;
            $y = $centro + ($raio + 20) * $seno;

            $ancora = 'middle';

            if ($cosseno > 0.25) {
                $ancora = 'start';
            } elseif ($cosseno < -0.25) {
                $ancora = 'end';
            }

            if ($seno > 0.6) {
                $y += 6;
            } elseif ($seno < -0.6) {
                $y -= 2;
            }

            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" text-anchor="%s" class="pc-radar-label">%s</text>',
                $x,
                $y,
                $ancora,
                htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8')
            );
        }

        // Séries
        foreach ($series as $indiceSerie => $serie) {
            $valores = $serie['valores'];
            $cor = $serie['cor'];
            $pontos = [];

            for ($i = 0; $i < $total; $i++) {
                $valor = max(0.0, min(100.0, (float) ($valores[$i] ?? 50)));
                $p = $ponto($i, $valor / 100);
                $pontos[] = $p['x'] . ',' . $p['y'];
            }

            $svg .= sprintf(
                '<polygon points="%s" class="pc-radar-serie" style="fill:%s;stroke:%s;%s"/>',
                implode(' ', $pontos),
                $cor,
                $cor,
                $indiceSerie > 0 ? 'fill-opacity:.06;stroke-dasharray:5 4' : ''
            );

            for ($i = 0; $i < $total; $i++) {
                $valor = max(0.0, min(100.0, (float) ($valores[$i] ?? 50)));
                $p = $ponto($i, $valor / 100);
                $corPonto = $coresPorEixo[$i] ?? $cor;

                $svg .= sprintf(
                    '<circle cx="%.2f" cy="%.2f" r="3.6" class="pc-radar-ponto" style="fill:%s"/>',
                    $p['x'],
                    $p['y'],
                    $corPonto
                );
            }
        }

        $svg .= '<circle cx="200" cy="200" r="2.4" class="pc-radar-centro"/>';
        $svg .= '</svg>';

        return $svg;
    }
}
