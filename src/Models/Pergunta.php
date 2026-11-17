<?php

declare(strict_types=1);

namespace Models;

use PDO;

final class Pergunta
{
    /** Quantidade de perguntas por modo. */
    public const MODOS = [
        'rapido'   => 36,
        'completo' => 60,
        'extremo'  => 240,
    ];

    /**
     * Perguntas de um modo de teste, na ordem de exibição.
     *
     * A cota é distribuída por eixo em passos de 2 (para manter o número de
     * afirmações de cada polo equilibrado) e as perguntas são intercaladas
     * entre os eixos. A cota de cada eixo só cresce com o modo, então o modo
     * menor é sempre um subconjunto do maior, com as mesmas perguntas por eixo.
     *
     * @return list<array<string, mixed>>
     */
    public static function paraModo(PDO $pdo, string $modo): array
    {
        $modo = isset(self::MODOS[$modo]) ? $modo : 'completo';
        $limite = self::MODOS[$modo];

        $porEixo = [];

        foreach (self::todas($pdo) as $pergunta) {
            $porEixo[(int) $pergunta['eixo_id']][] = $pergunta;
        }

        $total = count($porEixo);

        if ($total === 0) {
            return [];
        }

        $porEixo = array_values($porEixo);
        $cotas = self::cotas($limite, array_map('count', $porEixo));

        // Intercala os eixos: 1ª de cada eixo, depois a 2ª de cada eixo, etc.
        $selecionadas = [];

        for ($posicao = 0; ; $posicao++) {
            $achou = false;

            foreach ($porEixo as $indice => $perguntas) {
                if ($posicao < $cotas[$indice] && isset($perguntas[$posicao])) {
                    $selecionadas[] = $perguntas[$posicao];
                    $achou = true;
                }
            }

            if (!$achou) {
                break;
            }
        }

        return $selecionadas;
    }

    /**
     * Cota de perguntas por eixo para um modo.
     *
     * Distribui em passos de 2 para que cada eixo receba o mesmo número de
     * afirmações dos dois polos, e nunca passa do que o eixo tem.
     *
     * @param list<int> $disponiveis quantidade de perguntas por eixo
     * @return list<int>
     */
    public static function cotas(int $limite, array $disponiveis): array
    {
        $total = count($disponiveis);
        $cotas = array_fill(0, $total, 0);

        if ($total === 0 || $limite <= 0) {
            return $cotas;
        }

        $restante = $limite;
        $progresso = true;

        while ($restante > 1 && $progresso) {
            $progresso = false;

            for ($i = 0; $i < $total && $restante > 1; $i++) {
                if ($cotas[$i] + 2 > $disponiveis[$i]) {
                    continue;
                }

                $cotas[$i] += 2;
                $restante -= 2;
                $progresso = true;
            }
        }

        return $cotas;
    }

    /** @return list<array<string, mixed>> */
    public static function todas(PDO $pdo): array
    {
        $linhas = $pdo->query(
            'SELECT id, eixo_id, texto_key, texto, direcao, peso, modo_rapido, ordem, tmdb_id,
                    midia_tipo, midia_url_ou_id, midia_meta
               FROM perguntas
              ORDER BY ordem, id'
        )->fetchAll();

        return array_map([self::class, 'normalizar'], $linhas);
    }

    /**
     * Formato enxuto consumido pelo LikertCalculatorService.
     *
     * @param list<array<string, mixed>> $perguntas
     * @return array<int, array{eixo_id: int, direcao: int, peso: int}>
     */
    public static function mapaParaCalculo(array $perguntas): array
    {
        $mapa = [];

        foreach ($perguntas as $pergunta) {
            $mapa[(int) $pergunta['id']] = [
                'eixo_id' => (int) $pergunta['eixo_id'],
                'direcao' => (int) $pergunta['direcao'],
                'peso'    => (int) $pergunta['peso'],
            ];
        }

        return $mapa;
    }

    /** @return list<array<string, mixed>> */
    public static function porEixo(PDO $pdo): array
    {
        $linhas = $pdo->query(
            'SELECT p.id, p.texto_key, p.texto, p.direcao, p.peso, p.modo_rapido, p.ordem, p.eixo_id, p.tmdb_id,
                    p.midia_tipo, p.midia_url_ou_id, p.midia_meta
               FROM perguntas p
               JOIN eixos e ON e.id = p.eixo_id
              ORDER BY e.ordem, p.ordem, p.id'
        )->fetchAll();

        return array_map([self::class, 'normalizar'], $linhas);
    }

    /** @param array<string, mixed> $linha @return array<string, mixed> */
    private static function normalizar(array $linha): array
    {
        $linha['id'] = (int) $linha['id'];
        $linha['eixo_id'] = (int) $linha['eixo_id'];
        $linha['direcao'] = (int) $linha['direcao'];
        $linha['peso'] = (int) $linha['peso'];
        $linha['modo_rapido'] = (int) $linha['modo_rapido'];
        $linha['ordem'] = (int) $linha['ordem'];
        $linha['tmdb_id'] = isset($linha['tmdb_id']) && $linha['tmdb_id'] !== null
            ? (int) $linha['tmdb_id']
            : null;
        $linha['midia_tipo'] = isset($linha['midia_tipo']) && $linha['midia_tipo'] !== ''
            ? (string) $linha['midia_tipo']
            : null;
        $linha['midia_url_ou_id'] = isset($linha['midia_url_ou_id']) && $linha['midia_url_ou_id'] !== ''
            ? (string) $linha['midia_url_ou_id']
            : null;

        $meta = $linha['midia_meta'] ?? null;

        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }

        $linha['midia_meta'] = is_array($meta) ? $meta : [];

        return $linha;
    }
}
