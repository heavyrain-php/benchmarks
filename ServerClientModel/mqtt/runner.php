<?php

/**
 * @license MIT
 */

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

require_once __DIR__ . '/../vendor/autoload.php';

$host = 'localhost';
$port = 1883;
$logger = new Logger('HEAVYRAIN-AGGREGATOR', [
    new StreamHandler('php://output'),
], [
    new PsrLogMessageProcessor(),
]);

$mqtt = new MqttClient(
    host: $host,
    port: $port,
    logger: $logger,
);

$connectionSettings = (new ConnectionSettings())
    ->useBlockingSocket(false)
    ->setReconnectAutomatically(true)
    ->setDelayBetweenReconnectAttempts(1);

$mqtt->connect($connectionSettings, true);
$clientId = $mqtt->getClientId();
$mqtt->publish(\sprintf('heavyrain/runner/%s/connect', $clientId), 'hello', 1);
$mqtt->disconnect();
