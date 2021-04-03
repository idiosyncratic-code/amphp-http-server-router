<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FastRouteDispatcherTest extends TestCase
{
    use MocksHttpRequests;

    public function testRouteMatched() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $dispatcher = new FastRouteDispatcher($container);

        $routes = [
            new Route(
                'GET',
                '/hello',
                new TestRequestHandler(),
            ),
        ];

        $dispatcher->setRoutes($routes);

        $request = $this->mockRequest('GET', '/hello');

        $this->assertInstanceOf(RouteHandler::class, $dispatcher->dispatch($request));
    }

    public function testRouteNotFound() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $dispatcher = new FastRouteDispatcher($container);

        $routes = [
            new Route(
                'GET',
                '/hello',
                new TestRequestHandler(),
            ),
        ];

        $dispatcher->setRoutes($routes);

        $request = $this->mockRequest('GET', '/goodbye');

        $this->expectException(Exception\NotFound::class);

        $dispatcher->dispatch($request);
    }

    public function testBadMethodRequest() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $dispatcher = new FastRouteDispatcher($container);

        $routes = [
            new Route(
                'GET',
                '/hello',
                new TestRequestHandler(),
            ),
        ];

        $dispatcher->setRoutes($routes);

        $request = $this->mockRequest('PUT', '/hello');

        $this->expectException(Exception\MethodNotAllowed::class);

        $dispatcher->dispatch($request);
    }

}
