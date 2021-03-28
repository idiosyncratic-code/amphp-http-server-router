<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Amp\Success;
use Error;
use FastRoute\Dispatcher as FastRoute;
use FastRoute\RouteCollector;
use Idiosyncratic\AmpRoute\Exception\MethodNotAllowed;
use Idiosyncratic\AmpRoute\Exception\NotFound;
use Psr\Container\ContainerInterface;

use function array_map;
use function count;
use function FastRoute\simpleDispatcher;
use function is_string;
use function sprintf;

final class FastRouteDispatcher implements Dispatcher
{
    private ContainerInterface $container;

    private FastRoute $dispatcher;

    private bool $compiled = false;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(string $method, string $path) : DispatchResult
    {
        $dispatched = $this->dispatcher->dispatch($method, $path);

        if ($dispatched[0] === FastRoute::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowed($dispatched[1]);
        }

        if ($dispatched[0] === FastRoute::NOT_FOUND) {
            throw new NotFound();
        }

        $requestHandler = is_string($dispatched[1]['handler']) ?
            $this->container->get($dispatched[1]['handler']) :
            $dispatched[1]['handler'];

        if (count($dispatched[1]['middleware']) > 0) {
            $requestHandler = $this->makeMiddlewareRequestHandler($requestHandler, $dispatched[1]['middleware']);
        }

        return new DispatchResult(
            $requestHandler,
            $dispatched[2],
        );
    }

    /**
     * @inheritdoc
     */
    public function compile(array $routes) : void
    {
        if ($this->compiled() === true) {
            throw new Error('Routes already compiled');
        }

        $this->dispatcher = simpleDispatcher(static function (RouteCollector $collector) use ($routes) : void {
            foreach ($routes as [$method, $uri, $requestHandler]) {
                $uri = sprintf('/%s', $uri);

                $collector->addRoute($method, $uri, $requestHandler);
            }
        });

        $this->compiled = true;
    }

    public function compiled() : bool
    {
        return $this->compiled;
    }

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server) : Promise
    {
        return new Success();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        return new Success();
    }

    /**
     * @param array<string | Middleware> $middleware
     */
    private function makeMiddlewareRequestHandler(
        RequestHandler $requestHandler,
        array $middleware,
    ) : RequestHandler {
        $middleware = array_map(function ($item) {
            return $item instanceof Middleware ?
                $item :
                $this->container->get($item);
        }, $middleware);

        return new MiddlewareRequestHandler($requestHandler, ...$middleware);
    }
}
