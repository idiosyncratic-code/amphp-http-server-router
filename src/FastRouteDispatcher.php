<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use FastRoute\Dispatcher as FastRoute;
use FastRoute\RouteCollector;
use Idiosyncratic\AmpRoute\Exception\MethodNotAllowed;
use Idiosyncratic\AmpRoute\Exception\NotFound;
use Psr\Container\ContainerInterface;

use function FastRoute\simpleDispatcher;

final class FastRouteDispatcher implements Dispatcher
{
    private ContainerInterface $container;

    private FastRoute $dispatcher;

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
