<?php

declare(strict_types=1);

/**
 * Bootstrap comum a todas as páginas e endpoints.
 *
 * Uso:
 *   $config = require BASE_PATH . '/src/bootstrap.php';
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/autoload.php';
require BASE_PATH . '/src/Core/helpers.php';

\Core\Env::carregar(BASE_PATH . '/config/env.php');

$config = require BASE_PATH . '/config/app.php';

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

ini_set('display_errors', $config['debug'] ? '1' : '0');
error_reporting(E_ALL);

\Core\Database::configurar(require BASE_PATH . '/config/database.php');
\Core\Session::iniciar();
\Core\Translator::init((string) $config['locale'], BASE_PATH . '/lang');

set_exception_handler(static function (Throwable $erro) use ($config): void {
    error_log(sprintf(
        '[PersonaCut] %s em %s:%d',
        $erro->getMessage(),
        $erro->getFile(),
        $erro->getLine()
    ));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $erro->getMessage() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);

    if ($config['debug']) {
        echo '<pre style="padding:24px;font:14px/1.5 monospace;white-space:pre-wrap">';
        echo e($erro->getMessage() . "\n\n" . $erro->getTraceAsString());
        echo '</pre>';
        return;
    }

    echo '<h1>Algo saiu do projetor.</h1><p>Tente novamente em instantes.</p>';
});

return $config;
