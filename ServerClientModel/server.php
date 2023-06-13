<?php

/**
 * @license MIT
 */

declare(strict_types=1);

use Amp\CancelledException;
use Amp\SignalCancellation;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function Amp\async;
use function Amp\Socket\listen;

require_once __DIR__ . '/vendor/autoload.php';

$address = 'tcp://0.0.0.0:8080';
$logger = new Logger('HEAVYRAIN-BENCHMARK', [
    new StreamHandler('php://output'),
]);
$cancellation = new SignalCancellation(\SIGINT, 'Cancelled by keyboard interraption');

$server = listen($address);
$logger->info('Server process is starting to accept socket', compact('address'));

$mainFuture = async(static function () use ($server, $logger, $cancellation): void {
    while ($socket = $server->accept($cancellation)) {
        if ($server->isClosed()) {
            // Server is already stopped
            $socket->close();
            return;
        }
        $clientId = \uniqid();
        $remoteAddress = $socket->getRemoteAddress()->__toString();
        $logger->info('Client has connected', compact('clientId', 'remoteAddress'));
        $socket->onClose(static function () use ($clientId, $logger): void {
            $logger->info('Client has disconnected', compact('clientId'));
        });
        $socket->write($clientId);
        $socket->close();
    }
});

try {
    $mainFuture->await($cancellation);
} catch (CancelledException $exception) {
    $logger->notice('Server is going to be closed gracefully');
} catch (Throwable $exception) {
    $logger->error('Server has uncaught error. Exited', compact('exception'));
} finally {
    if (!$server->isClosed()) {
        $server->close();
    }
}

if ($cancellation->isRequested()) {
    $logger->notice('Server has cancelled.');
    exit(2);
}

$logger->info('Server has closed. This process will be exit');
