<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace ServerClientModel;

use Amp\Socket\ResourceSocket;
use Psr\Log\LoggerInterface;

class ServerClient
{
    public function __construct(
        private readonly ResourceSocket $socket,
        public readonly string $clientId,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function dispose(): void
    {
        if (!$this->socket->isClosed()) {
            $this->socket->close();
        }
        $this->logger->info('Client disconnected', ['clientId' => $this->clientId]);
    }

    public function handle(): void
    {
        $this->socket->write($this->clientId);
    }
}
