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

use function array_map;
use function count;
use function FastRoute\simpleDispatcher;
use function is_string;

final class FastRouteDispatcher implements Dispatcher
{
    private ContainerInterface $container;

    private FastRoute $dispatcher;

    /** @var array<Route> */
    private array $routes;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(Request $request) : RouteHandler
    {
        $dispatched = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($dispatched[0] === FastRoute::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowed($dispatched[1]);
        }

        if ($dispatched[0] === FastRoute::NOT_FOUND) {
            throw new NotFound();
        }

        return new RouteHandler(
            $dispatched[1]->resolveRequestHandler($this->container),
            $dispatched[2],
        );
    }

    /**
     * @inheritdoc
     */
    public function setRoutes(array $routes) : void
    {
        $this->dispatcher = simpleDispatcher(static function (RouteCollector $collector) use ($routes) : void {
            foreach ($routes as $route) {
                $path = $route->getPath();

                $collector->addRoute($route->getMethod(), $path, $route);
            }
        });
    }
}
