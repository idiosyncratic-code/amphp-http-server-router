<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Amp\Success;

use function Amp\call;

class TestMiddleware implements Middleware
{
    private string $header;

    private string $value;

    public function __construct(string $header, string $value)
    {
        $this->header = $header;

        $this->value = $value;
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Promise
    {
        return call(function () use ($request, $requestHandler) {
            $response = yield $requestHandler->handleRequest($request);

            $response->setHeader($this->header, $this->value);

            return new Success($response);
        });
    }
}
