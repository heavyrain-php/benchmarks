<?php

/**
 * @license MIT
 */

declare(strict_types=1);

use function Amp\Socket\connect;

require_once __DIR__ . '/vendor/autoload.php';

$uri = 'tcp://localhost:8080';
$socket = connect($uri);

$received = $socket->read();
\printf('Received: %s' . PHP_EOL, $received);

$socket->close();

echo 'End.' . PHP_EOL;
