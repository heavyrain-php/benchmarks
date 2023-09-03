<?php

/**
 * @license MIT
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Amp\Http\Client\Connection\Stream;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\EventListener;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;

use function Amp\async;
use function Amp\now;

enum TimingKey: string
{
    case START_REQUEST = 'heavyrain.start_request';
    case START_DNS_RESOLUTION = 'heavyrain.start_dns_resolution';
    case COMPLETE_DNS_RESOLUTION = 'heavyrain.complete_dns_resolution';
    case START_CONNECTION_CREATION = 'heavyrain.start_connection_creation';
    case COMPLETE_CONNECTION_CREATION = 'heavyrain.complete_connection_creation';
    case START_TLS_NEGOTIATION = 'heavyrain.start_tls_negotiation';
    case COMPLETE_TLS_NEGOTIATION = 'heavyrain.complete_tls_negotiation';
    case START_SENDING_REQUEST = 'heavyrain.start_sending_request';
    case COMPLETE_SENDING_REQUEST = 'heavyrain.complete_sending_request';
    case START_RECEIVING_RESPONSE = 'heavyrain.start_receiving_response';
    case COMPLETE_RECEIVING_RESPONSE = 'heavyrain.complete_receiving_response';
    case ABORT = 'heavyrain.abort';
}

$recorder = new class () implements EventListener {
    public function startRequest(Request $request): void
    {
        $this->addTiming(TimingKey::START_REQUEST, $request);
    }

    public function startDnsResolution(Request $request): void
    {
        $this->addTiming(TimingKey::START_DNS_RESOLUTION, $request);
    }

    public function completeDnsResolution(Request $request): void
    {
        $this->addTiming(TimingKey::COMPLETE_DNS_RESOLUTION, $request);
    }

    public function startConnectionCreation(Request $request): void
    {
        $this->addTiming(TimingKey::START_CONNECTION_CREATION, $request);
    }

    public function completeConnectionCreation(Request $request): void
    {
        $this->addTiming(TimingKey::COMPLETE_CONNECTION_CREATION, $request);
    }

    public function startTlsNegotiation(Request $request): void
    {
        $this->addTiming(TimingKey::START_TLS_NEGOTIATION, $request);
    }

    public function completeTlsNegotiation(Request $request): void
    {
        $this->addTiming(TimingKey::COMPLETE_TLS_NEGOTIATION, $request);
    }

    public function startSendingRequest(Request $request, Stream $stream): void
    {
        $this->addTiming(TimingKey::START_SENDING_REQUEST, $request);
    }

    public function completeSendingRequest(Request $request, Stream $stream): void
    {
        $this->addTiming(TimingKey::COMPLETE_SENDING_REQUEST, $request);
    }

    public function startReceivingResponse(Request $request, Stream $stream): void
    {
        $this->addTiming(TimingKey::START_RECEIVING_RESPONSE, $request);
    }

    public function completeReceivingResponse(Request $request, Stream $stream): void
    {
        $this->addTiming(TimingKey::COMPLETE_RECEIVING_RESPONSE, $request);
    }

    public function abort(Request $request, \Throwable $cause): void
    {
        $this->addTiming(TimingKey::ABORT, $request);
    }

    private function addTiming(TimingKey $key, Request $request): void
    {
        if (!$request->hasAttribute($key->value)) {
            $request->setAttribute($key->value, now());
        }
    }
};

// set up client
$pool = new UnlimitedConnectionPool();

$future = async(static function () use ($pool, $recorder) {
    $client = (new HttpClientBuilder())->usingPool($pool)->build();
    $requester = static function () use ($client, $recorder) {
        // execute request
        $request = new Request('http://localhost:8081');
        $request->addEventListener($recorder);
        $response = $client->request($request);

        // wait until closing response
        $body = $response->getBody()->buffer();

        return $request->getAttributes();
    };
    while (true) {
        yield $requester();
        \sleep(1);
    }
});

$result = $future->await();
foreach ($result as $r) {
    var_dump($r);
}
