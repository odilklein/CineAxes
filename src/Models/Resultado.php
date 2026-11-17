<?php

declare(strict_types=1);

namespace Models;

use Core\Vetor;
use PDO;

final class Resultado
{
    /** Alfabeto sem caracteres ambíguos (0/O, 1/I/L). 32 símbolos, 8 posições. */
    private const ALFABETO = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function gerarCodigo(): string
    {
        $codigo = '';

        for ($i = 0; $i < 8; $i++) {
            $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return $codigo;
    }

    public static function codigoExiste(PDO $pdo, string $codigo): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM resultados_usuarios WHERE codigo_unico = :codigo LIMIT 1');
        $stmt->execute(['codigo' => $codigo]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<string, mixed> $dados
     */
    public static function criar(PDO $pdo, array $dados): int
    {
        $colunas = Vetor::paraColunas($dados['vetor']);

        $sql = 'INSERT INTO resultados_usuarios
                    (codigo_unico, idioma, modo, respostas_json,
                     eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8,
                     arquetipo_id, origem, ip_hash)
                VALUES
                    (:codigo, :idioma, :modo, :respostas,
                     :eixo_1, :eixo_2, :eixo_3, :eixo_4, :eixo_5, :eixo_6, :eixo_7, :eixo_8,
                     :arquetipo_id, :origem, :ip_hash)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'codigo'       => $dados['codigo'],
            'idioma'       => $dados['idioma'] ?? 'pt-BR',
            'modo'         => $dados['modo'] ?? 'completo',
            'respostas'    => $dados['respostas_json'] ?? null,
            'eixo_1'       => $colunas['eixo_1'],
            'eixo_2'       => $colunas['eixo_2'],
            'eixo_3'       => $colunas['eixo_3'],
            'eixo_4'       => $colunas['eixo_4'],
            'eixo_5'       => $colunas['eixo_5'],
            'eixo_6'       => $colunas['eixo_6'],
            'eixo_7'       => $colunas['eixo_7'],
            'eixo_8'       => $colunas['eixo_8'],
            'arquetipo_id' => $dados['arquetipo_id'] ?? null,
            'origem'       => $dados['origem'] ?? 'real',
            'ip_hash'      => $dados['ip_hash'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public static function porCodigo(PDO $pdo, string $codigo): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, codigo_unico, idioma, modo, eixo_1, eixo_2, eixo_3, eixo_4,
                    eixo_5, eixo_6, eixo_7, eixo_8, arquetipo_id, created_at
               FROM resultados_usuarios
              WHERE codigo_unico = :codigo
              LIMIT 1'
        );
        $stmt->execute(['codigo' => strtoupper(trim($codigo))]);
        $linha = $stmt->fetch();

        if ($linha === false) {
            return null;
        }

        $linha['id'] = (int) $linha['id'];
        $linha['arquetipo_id'] = $linha['arquetipo_id'] === null ? null : (int) $linha['arquetipo_id'];
        $linha['vetor'] = Vetor::daLinha($linha);

        return $linha;
    }

    /**
     * Vetores usados no cálculo de raridade (reais + sintéticos).
     *
     * @return list<list<float>>
     */
    public static function vetores(PDO $pdo, int $limite = 5000): array
    {
        $limite = max(1, min(20000, $limite));

        $stmt = $pdo->prepare(
            'SELECT eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8
               FROM resultados_usuarios
              ORDER BY created_at DESC
              LIMIT :limite'
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $linha): array => Vetor::daLinha($linha),
            $stmt->fetchAll()
        );
    }

    public static function total(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM resultados_usuarios')->fetchColumn();
    }

    public static function totalReais(PDO $pdo): int
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM resultados_usuarios WHERE origem = 'real'")->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function ultimos(PDO $pdo, int $limite = 10): array
    {
        $stmt = $pdo->prepare(
            'SELECT codigo_unico, modo, eixo_1, eixo_2, eixo_3, eixo_4, eixo_5, eixo_6, eixo_7, eixo_8,
                    arquetipo_id, created_at
               FROM resultados_usuarios
              WHERE origem = :origem
              ORDER BY created_at DESC
              LIMIT :limite'
        );
        $stmt->bindValue('origem', 'real');
        $stmt->bindValue('limite', max(1, $limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function contarPorArquetipo(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT a.id, a.titulo, COUNT(r.id) AS total
               FROM arquetipos a
               LEFT JOIN resultados_usuarios r ON r.arquetipo_id = a.id AND r.origem = 'real'
              GROUP BY a.id, a.titulo
              ORDER BY total DESC, a.titulo"
        )->fetchAll();
    }
}
