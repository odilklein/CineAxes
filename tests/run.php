<?php

declare(strict_types=1);

/**
 * Executa todos os testes: php tests/run.php
 */

require dirname(__DIR__) . '/src/autoload.php';

echo "\n  PersonaCut · testes de serviço\n";
echo str_repeat('─', 54) . "\n";

require __DIR__ . '/LikertTest.php';
require __DIR__ . '/MatcherTest.php';
require __DIR__ . '/MidiaTest.php';
require __DIR__ . '/SceneTest.php';
require __DIR__ . '/RadarTest.php';
require __DIR__ . '/LeituraTest.php';
require __DIR__ . '/ModosTest.php';
require __DIR__ . '/EixoGuiaTest.php';
require __DIR__ . '/CatalogoTest.php';
require __DIR__ . '/RateLimitTest.php';

exit(resumoTestes());
