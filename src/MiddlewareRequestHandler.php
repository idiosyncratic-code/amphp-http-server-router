<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\ServerObserver;
use Amp\Failure;
use Amp\Promise;
use Error;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function array_shift;
use function array_walk;
use function get_class;
use function sprintf;

final class MiddlewareRequestHandler implements RequestHandler, ServerObserver
{
    private RequestHandler $requestHandler;

    /** @var array<Middleware> */
    private array $middleware;

    /** @var SplObjectStorage<ServerObserver, null> */
    private SplObjectStorage $observers;

    private HttpServer $server;

    private bool $running = false;

    public function __construct(
        RequestHandler $requestHandler,
        Middleware ...$middleware
    ) {
        $this->requestHandler = $requestHandler;

        $this->middleware = $middleware;

        $this->observers = new SplObjectStorage();
    }

    public function handleRequest(Request $request) : Promise
    {
        $handler = clone $this;

        $middleware = $handler->getNextHandler();

        $currentHandler = $middleware instanceof Middleware ?
            $middleware :
            $this->requestHandler;

        $this->getLogger()->debug(sprintf('Executing handler %s', get_class($currentHandler)));

        return $middleware instanceof Middleware ?
            $middleware->handleRequest($request, $handler) :
            $this->requestHandler->handleRequest($request);
    }

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server) : Promise
    {
        if ($this->running) {
            return new Failure(new Error('Router already started'));
        }

        $this->server = $server;

        $this->running = true;

        if ($this->requestHandler instanceof ServerObserver) {
            $this->observers->attach($this->requestHandler);
        }

        array_walk(
            $this->middleware,
            function ($middleware) : void {
                if (! ($middleware instanceof ServerObserver)) {
                    return;
                }

                $this->observers->attach($middleware);
            },
        );

        $promises = [];

        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStart($server);
        }

        /** @phpstan-ignore-next-line */
        return Promise\all($promises);
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        $this->running = false;

        $promises = [];

        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStop($server);
        }

        /** @phpstan-ignore-next-line */
        $this->observers->removeAll($this->observers);

        /** @phpstan-ignore-next-line */
        return Promise\all($promises);
    }

    /**
     * Gets the next Middleware off of the middleware stack, or null if the end of the
     * stack is reached
     */
    protected function getNextHandler() : ?Middleware
    {
        return array_shift($this->middleware);
    }

    private function getLogger() : LoggerInterface
    {
        return $this->server->getLogger();
    }
}
