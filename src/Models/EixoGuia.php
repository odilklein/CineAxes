<?php

declare(strict_types=1);

namespace Models;

/**
 * Guia editorial dos eixos (texto do modal "Entender este eixo").
 *
 * O conteúdo é estático e versionado em src/Data/eixos_guia.php: ele não muda
 * com o banco e precisa funcionar mesmo quando o catálogo está só no fallback
 * dos arquivos de tradução.
 */
final class EixoGuia
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $cache = null;

    /**
     * Todos os guias, indexados pelo slug do eixo.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function todos(): array
    {
        if (self::$cache === null) {
            /** @var array<string, array<string, mixed>> $guias */
            $guias = require __DIR__ . '/../Data/eixos_guia.php';
            self::$cache = $guias;
        }

        return self::$cache;
    }

    /**
     * Guia de um eixo. Devolve uma estrutura vazia quando o slug é desconhecido,
     * para o front nunca receber null.
     *
     * @return array<string, mixed>
     */
    public static function porSlug(string $slug): array
    {
        $guias = self::todos();

        return $guias[$slug] ?? [
            'resumo'    => '',
            'paragrafos' => [],
            'leitura'   => ['esq' => '', 'dir' => ''],
            'cenas'     => [],
            'citacoes'  => [],
        ];
    }
}
