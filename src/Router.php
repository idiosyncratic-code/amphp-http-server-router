<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;
use Idiosyncratic\AmpRoute\Exception\NotFound;

use function array_push;
use function array_reduce;

final class Router implements RequestHandler, ServerObserver
{
    use HasServerObservers;

    private Dispatcher $dispatcher;

    private RouteGroup $routes;

    private ?RequestHandler $defaultHandler;

    public function __construct(
        Dispatcher $dispatcher,
        RouteGroup $routes,
        ?RequestHandler $defaultHandler = null,
    ) {
        $this->dispatcher = $dispatcher;

        $this->routes = $routes;

        $this->defaultHandler = $defaultHandler;
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
     * @return array<ServerObserver>
     *
     * phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
     */
    private function getServerObservers() : array
    {
        $observers = [];

        if ($this->dispatcher instanceof ServerObserver) {
            $observers[] = $this->dispatcher;
        }

        if ($this->defaultHandler instanceof ServerObserver) {
            $observers[] = $this->defaultHandler;
        }

        $routes = $this->routes->getRoutes();

        $this->dispatcher->setRoutes($routes);

        return array_reduce($routes, static function ($observers, $route) {
            array_push($observers, ...$route->getServerObservers());

            return $observers;
        }, $observers);
    }
}
