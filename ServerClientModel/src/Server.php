<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace ServerClientModel;

use Amp\Future;
use Amp\Socket\SocketAddress;
use Psr\Log\LoggerInterface;

use function Amp\async;
use function Amp\Socket\listen;

class Server
{
    public function __construct(
        private readonly SocketAddress|string $address,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function start(): Future
    {
        $server = listen($this->address);
        $this->logger->info('Server is start to listen', ['address' => $this->address]);
        $logger = $this->logger;

        return async(static function () use ($server, $logger): void {
            while ($socket = $server->accept()) {
                $clientId = \uniqid('HEAVYRAIN-');
                $logger->info('Client connected', ['remoteAddress' => $socket->getRemoteAddress()->__toString(), 'clientId' => $clientId]);
                $serverClient = new ServerClient($socket, $clientId, $logger);
                $socket->onClose(static function () use ($serverClient): void {
                    if (!\is_null($serverClient)) {
                        $serverClient->dispose();
                        $serverClient = null;
                    }
                });
                $serverClient->handle();
            }
        });
    }
}
