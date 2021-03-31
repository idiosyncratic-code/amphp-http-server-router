<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;
use Amp\Success;

use function Amp\call;

class TestMiddlewareObserver implements Middleware, ServerObserver
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

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server): Promise
    {
        return new Success();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server): Promise
    {
        return new Success();
    }
}
