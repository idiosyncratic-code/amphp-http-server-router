<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;

use function array_shift;

final class MiddlewareRequestHandler implements RequestHandler
{
    private RequestHandler $requestHandler;

    /** @var array<Middleware> */
    private array $middleware;

    public function __construct(
        RequestHandler $requestHandler,
        Middleware ...$middleware
    ) {
        $this->requestHandler = $requestHandler;

        $this->middleware = $middleware;
    }

    public function handleRequest(Request $request) : Promise
    {
        $handler = clone $this;

        $middleware = $handler->getNextHandler();

        return $middleware instanceof Middleware ?
            $middleware->handleRequest($request, $handler) :
            $this->requestHandler->handleRequest($request);
    }

    /**
     * Gets the next Middleware off of the middleware stack, or null if the end of the
     * stack is reached
     */
    protected function getNextHandler() : ?Middleware
    {
        return array_shift($this->middleware);
    }
}
