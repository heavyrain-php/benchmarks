<?php

/**
 * @license MIT
 */

declare(strict_types=1);

use Amp\CancelledException;
use Amp\SignalCancellation;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function Amp\Socket\connect;

require_once __DIR__ . '/../vendor/autoload.php';

$uri = 'tcp://localhost:8080';
$logger = new Logger('HEAVYRAIN-BENCHMARK', [
    new StreamHandler('php://output'),
]);

try {
    $logger->info('Connecting to server', compact('uri'));
    $socket = connect($uri);
    $logger->info('Connected to server', compact('uri'));

    $received = $socket->read(new SignalCancellation(\SIGINT));
    $logger->info('Received', compact('received'));

    $socket->close();
} catch (CancelledException $exception) {
    $logger->notice('Client is going to be closed gracefully');
} catch (Throwable $exception) {
    $logger->error('Client has uncaught error. Exited', compact('exception'));
} finally {
    if ($socket && !$socket->isClosed()) {
        $logger->debug('Close connection');
        $socket->close();
    }
}

$logger->info('Finished');
