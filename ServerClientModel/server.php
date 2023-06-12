<?php

/**
 * @license MIT
 */

declare(strict_types=1);

use ServerClientModel\Server;

require_once __DIR__ . '/vendor/autoload.php';

$address = 'tcp://0.0.0.0:8080';
$logger = new Monolog\Logger('HEAVYRAIN-BENCHMARK', [
    new Monolog\Handler\StreamHandler('php://output'),
]);

$server = new Server($address, $logger);

$future = $server->start();

$future->await();

echo 'Server closed.' . PHP_EOL;
