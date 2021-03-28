<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;

final class DispatchResult implements RequestHandler
{
    private RequestHandler $handler;

    /** @var array<mixed> */
    private array $routeArgs;

    /**
     * @param array<mixed> $routeArgs
     */
    public function __construct(RequestHandler $handler, array $routeArgs)
    {
        $this->handler = $handler;

        $this->routeArgs = $routeArgs;
    }

    public function handleRequest(Request $request) : Promise
    {
        $request->setAttribute(Router::class, $this->routeArgs);

        return $this->handler->handleRequest($request);
    }
}
