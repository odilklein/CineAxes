<?php

declare(strict_types=1);

namespace Core;

/**
 * Tradução por arquivos JSON em lang/.
 *
 * - A chave usa notação de ponto: "ui.nav.start".
 * - Fallback: locale pedido -> pt-BR -> própria chave.
 * - Placeholders no formato {nome}.
 *
 * Hoje o app é 100% pt-BR; a estrutura já está pronta para EN/ES sem
 * reescrever as páginas.
 */
final class Translator
{
    private static string $locale = 'pt-BR';
    private static string $diretorio = '';
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    public static function init(string $locale, string $diretorio): void
    {
        self::$locale = $locale;
        self::$diretorio = rtrim($diretorio, '/');
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /**
     * @param array<string, string|int|float> $substituicoes
     */
    public static function get(string $chave, array $substituicoes = []): string
    {
        $texto = self::buscar(self::$locale, $chave)
            ?? self::buscar('pt-BR', $chave)
            ?? $chave;

        foreach ($substituicoes as $nome => $valor) {
            $texto = str_replace('{' . $nome . '}', (string) $valor, $texto);
        }

        return $texto;
    }

    private static function buscar(string $locale, string $chave): ?string
    {
        $no = self::buscarNo($locale, $chave);

        return is_string($no) ? $no : null;
    }

    /**
     * Listas e estruturas aninhadas do JSON (cards, passos, FAQ...).
     *
     * @return array<mixed>
     */
    public static function lista(string $chave): array
    {
        $no = self::buscarNo(self::$locale, $chave) ?? self::buscarNo('pt-BR', $chave);

        return is_array($no) ? $no : [];
    }

    private static function buscarNo(string $locale, string $chave): mixed
    {
        $dados = self::carregar($locale);
        $no = $dados;

        foreach (explode('.', $chave) as $parte) {
            if (!is_array($no) || !array_key_exists($parte, $no)) {
                return null;
            }
            $no = $no[$parte];
        }

        return $no;
    }

    /** @return array<string, mixed> */
    private static function carregar(string $locale): array
    {
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }

        $arquivo = self::$diretorio . '/' . $locale . '.json';

        if (!is_file($arquivo)) {
            self::$cache[$locale] = [];
            return [];
        }

        $conteudo = file_get_contents($arquivo);
        $dados = json_decode($conteudo === false ? '' : $conteudo, true);

        self::$cache[$locale] = is_array($dados) ? $dados : [];

        return self::$cache[$locale];
    }
}
