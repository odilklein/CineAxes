<?php

declare(strict_types=1);

use Core\RateLimit;

require_once __DIR__ . '/_assert.php';

teste('limitador libera até o limite e bloqueia depois', function (): void {
    $estado = [];
    $agora = 1_700_000_000;
    $liberados = 0;

    for ($i = 0; $i < 5; $i++) {
        $resultado = RateLimit::decidir($estado, $agora, 3, 60);
        $estado = $resultado['estado'];

        if ($resultado['ok']) {
            $liberados++;
        }
    }

    igual(3, $liberados, 'esperava exatamente três requisições liberadas');
    igual(false, $resultado['ok'], 'a quinta tentativa deveria ser bloqueada');
    afirmar($resultado['espera'] > 0, 'esperava um tempo de espera');
});

teste('limitador reinicia quando a janela expira', function (): void {
    $resultado = RateLimit::decidir(['inicio' => 100, 'contagem' => 10], 200, 3, 60);

    igual(true, $resultado['ok']);
    igual(1, $resultado['estado']['contagem']);
    igual(200, $resultado['estado']['inicio']);
});

teste('limitador grava e zera a contagem em arquivo', function (): void {
    $diretorio = sys_get_temp_dir() . '/pc-ratelimit-' . bin2hex(random_bytes(4));
    RateLimit::configurarDiretorio($diretorio);

    $chave = 'teste:' . bin2hex(random_bytes(3));
    $resultados = [];

    for ($i = 0; $i < 3; $i++) {
        $resultados[] = RateLimit::permitir($chave, 2, 60)['ok'];
    }

    igual([true, true, false], $resultados);

    RateLimit::limpar($chave);
    igual(true, RateLimit::permitir($chave, 2, 60)['ok']);

    foreach (glob($diretorio . '/*.json') ?: [] as $arquivo) {
        @unlink($arquivo);
    }

    @rmdir($diretorio);
    RateLimit::configurarDiretorio(null);
});
