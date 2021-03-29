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
use Error;
use FastRoute\Dispatcher as FastRoute;
use FastRoute\RouteCollector;
use Idiosyncratic\AmpRoute\Exception\MethodNotAllowed;
use Idiosyncratic\AmpRoute\Exception\NotFound;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function array_map;
use function count;
use function FastRoute\simpleDispatcher;
use function is_string;

final class FastRouteDispatcher implements Dispatcher
{
    private ContainerInterface $container;

    private FastRoute $dispatcher;

    private bool $compiled = false;

    private RouteCollection $routes;

    /** @var SplObjectStorage<ServerObserver, null> */
    private SplObjectStorage $observers;

    private HttpServer $server;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(Request $request) : DispatchResult
    {
        $dispatched = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($dispatched[0] === FastRoute::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowed($dispatched[1]);
        }

        if ($dispatched[0] === FastRoute::NOT_FOUND) {
            throw new NotFound();
        }

        $requestHandler = is_string($dispatched[1]->getRequestHandler()) ?
            $this->container->get($dispatched[1]->getRequestHandler()) :
            $dispatched[1]->getRequestHandler();

        if (count($dispatched[1]->getMiddleware()) > 0) {
            $requestHandler = $this->makeMiddlewareRequestHandler($requestHandler, $dispatched[1]->getMiddleware());
        }

        return new DispatchResult(
            $requestHandler,
            $dispatched[2],
        );
    }

    public function mapRoutes(RouteCollection $routes) : void
    {
        $this->routes = $routes;
    }

    public function compile(RouteCollection $routes) : void
    {
        if ($this->compiled() === true) {
            throw new Error('Routes already compiled');
        }

        $this->dispatcher = simpleDispatcher(static function (RouteCollector $collector) use ($routes) : void {
            foreach ($routes as $route) {
                $path = $route->getPath();

                $collector->addRoute($route->getMethod(), $path, $route);
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
        $this->server = $server;

        $this->compile($this->routes);

        return new Success();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        unset($this->server);

        return new Success();
    }

    private function getLogger() : LoggerInterface
    {
        return $this->server->getLogger();
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
