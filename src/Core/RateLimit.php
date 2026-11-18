<?php

declare(strict_types=1);

namespace Core;

/**
 * Limite de requisições por chave (IP, usuário, ação) em arquivo.
 *
 * Sem banco e sem dependência: cada chave vira um JSON pequeno em
 * storage/cache/ratelimit. Se o diretório não for gravável, o limitador
 * libera a requisição (fail-open) para nunca derrubar o site.
 *
 * Uso:
 *   $r = RateLimit::permitir('quiz:' . $ip, 12, 60);
 *   if (!$r['ok']) { header('Retry-After: ' . $r['espera']); ... }
 */
final class RateLimit
{
    /** Sobrescreve o diretório (testes). */
    private static ?string $diretorio = null;

    public static function configurarDiretorio(?string $diretorio): void
    {
        self::$diretorio = $diretorio;
    }

    /**
     * Conta uma tentativa para a chave.
     *
     * @return array{ok: bool, restante: int, espera: int}
     */
    public static function permitir(string $chave, int $limite, int $janela): array
    {
        $limite = max(1, $limite);
        $janela = max(1, $janela);
        $arquivo = self::arquivo($chave);

        self::garantirDiretorio(dirname($arquivo));

        $ponteiro = @fopen($arquivo, 'c+');

        if ($ponteiro === false) {
            return ['ok' => true, 'restante' => $limite, 'espera' => 0];
        }

        flock($ponteiro, LOCK_EX);

        $bruto = stream_get_contents($ponteiro);
        $estado = is_string($bruto) && $bruto !== '' ? json_decode($bruto, true) : [];
        $resultado = self::decidir(is_array($estado) ? $estado : [], time(), $limite, $janela);

        if ($resultado['ok']) {
            ftruncate($ponteiro, 0);
            rewind($ponteiro);
            fwrite($ponteiro, json_encode($resultado['estado']));
            fflush($ponteiro);
        }

        flock($ponteiro, LOCK_UN);
        fclose($ponteiro);

        self::limparAntigos();

        return [
            'ok'       => $resultado['ok'],
            'restante' => $resultado['restante'],
            'espera'   => $resultado['espera'],
        ];
    }

    /** Zera a contagem (ex.: login bem-sucedido). */
    public static function limpar(string $chave): void
    {
        $arquivo = self::arquivo($chave);

        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }

    /**
     * Regra pura, sem I/O — facilita o teste.
     *
     * @param array{inicio?: int, contagem?: int} $estado
     * @return array{ok: bool, estado: array{inicio: int, contagem: int}, restante: int, espera: int}
     */
    public static function decidir(array $estado, int $agora, int $limite, int $janela): array
    {
        $inicio = (int) ($estado['inicio'] ?? 0);
        $contagem = (int) ($estado['contagem'] ?? 0);

        if ($inicio <= 0 || $agora - $inicio >= $janela) {
            return [
                'ok'       => true,
                'estado'   => ['inicio' => $agora, 'contagem' => 1],
                'restante' => max(0, $limite - 1),
                'espera'   => 0,
            ];
        }

        if ($contagem >= $limite) {
            return [
                'ok'       => false,
                'estado'   => ['inicio' => $inicio, 'contagem' => $contagem],
                'restante' => 0,
                'espera'   => max(1, $janela - ($agora - $inicio)),
            ];
        }

        $contagem++;

        return [
            'ok'       => true,
            'estado'   => ['inicio' => $inicio, 'contagem' => $contagem],
            'restante' => max(0, $limite - $contagem),
            'espera'   => 0,
        ];
    }

    private static function arquivo(string $chave): string
    {
        return self::diretorio() . '/' . hash('sha256', $chave) . '.json';
    }

    private static function diretorio(): string
    {
        if (self::$diretorio !== null) {
            return rtrim(self::$diretorio, '/');
        }

        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

        return $base . '/storage/cache/ratelimit';
    }

    private static function garantirDiretorio(string $diretorio): void
    {
        if (!is_dir($diretorio)) {
            @mkdir($diretorio, 0775, true);
        }
    }

    /** Faxina ocasional: remove contadores parados há mais de dois dias. */
    private static function limparAntigos(): void
    {
        if (random_int(1, 100) > 2) {
            return;
        }

        $arquivos = glob(self::diretorio() . '/*.json');

        if (!is_array($arquivos)) {
            return;
        }

        $limite = time() - 172800;

        foreach ($arquivos as $arquivo) {
            $modificado = @filemtime($arquivo);

            if ($modificado !== false && $modificado < $limite) {
                @unlink($arquivo);
            }
        }
    }
}
