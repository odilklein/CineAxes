<?php

declare(strict_types=1);

/** Exige sessão administrativa antes de renderizar a página. */

require __DIR__ . '/_init.php';

\Core\SessionUser::exigirLogin();

$admin = \Core\SessionUser::atual();
