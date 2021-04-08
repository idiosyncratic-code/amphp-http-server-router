<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Failure;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;
use Error;
use Idiosyncratic\AmpRoute\Exception\NotFound;
use Psr\Log\LoggerInterface;

use function array_walk;

final class Router implements RequestHandler, ServerObserver
{
    use HasServerObservers;

    private Dispatcher $dispatcher;

    private RouteGroup $routes;

    private ?RequestHandler $defaultHandler;

    private HttpServer $server;

    private bool $running = false;

    public function __construct(
        Dispatcher $dispatcher,
        RouteGroup $routes,
        ?RequestHandler $defaultHandler = null,
    ) {
        $this->dispatcher = $dispatcher;

        $this->routes = $routes;

        $this->defaultHandler = $defaultHandler;

        $this->setupObservers();
    }

    public function handleRequest(Request $request) : Promise
    {
        try {
            return $this->dispatcher->dispatch($request)
                   ->handleRequest($request);
        } catch (NotFound $t) {
            if ($this->defaultHandler instanceof RequestHandler) {
                return $this->defaultHandler->handleRequest($request);
            }

            throw $t;
        }
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

        $this->getLogger()->debug('Starting Router');

        $this->running = true;

        $routes = $this->routes->getRoutes();

        $this->dispatcher->setRoutes($routes);

        array_walk(
            $routes,
            function ($route) : void {
                foreach ($route->getServerObservers() as $observer) {
                    $this->addObserver($observer);
                }
            },
        );

        if ($this->dispatcher instanceof ServerObserver) {
            $this->addObserver($this->dispatcher);
        }

        return $this->startObservers($server);
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        $this->running = false;

        return $this->stopObservers($server);
    }

    private function getLogger() : LoggerInterface
    {
        return $this->server->getLogger();
    }
}
