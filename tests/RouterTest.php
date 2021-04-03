<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Error;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    use MocksHttpRequests;

    use MocksHttpServer;

    public function testStartAndStop() : void
    {
        $mockDispatcher = $this->createMock(TestServerObserverDispatcher::class);

        $mockDispatcher->expects($this->exactly(1))
             ->method('onStart')
             ->will($this->returnValue(new Success()));

        $mockDispatcher->expects($this->exactly(1))
             ->method('onStop')
             ->will($this->returnValue(new Success()));


        $handler = new TestRequestHandlerObserver();

        $middleware1 = new TestMiddleware('x-foo', 'bar');

        $middleware2 = new TestMiddleware('x-bar', 'baz');

        $middleware3 = new TestMiddlewareObserver('x-baz', 'foo');

        $routeGroup = new RouteGroup(
            '/hello',
            static function (RouteGroup $group) use ($handler, $middleware2, $middleware3) : void {
                $group->map(
                    'GET',
                    '/world',
                    $handler,
                    $middleware2,
                );

                $group->map(
                    'GET',
                    '/universe',
                    $handler,
                    $middleware2,
                    $middleware3,
                );
            },
            $middleware1
        );

        $server = $this->mockHttpServer();

        $router = new Router($mockDispatcher, $routeGroup);

        $router->onStart($server);

        $router->onStop($server);
    }

    public function testDoubleStartFails() : void
    {
        $mockDispatcher = $this->createMock(TestServerObserverDispatcher::class);

        $handler = new TestRequestHandlerObserver();

        $routeGroup = new RouteGroup(
            '/hello',
            static function (RouteGroup $group) use ($handler) : void {
                $group->map(
                    'GET',
                    '/world',
                    $handler,
                );
            },
        );

        $server = $this->mockHttpServer();

        $router = new Router($mockDispatcher, $routeGroup);

        $router->onStart($server);

        $this->expectException(Error::class);

        Promise\wait($router->onStart($server));
    }

    public function testHandlesRequests() : void
    {
        $handler = new TestRequestHandlerObserver();

        $mockDispatcher = $this->createMock(TestServerObserverDispatcher::class);

        $result = new RouteHandler($handler, []);

        $request = $this->mockRequest('GET', '/hello/world');

        $mockDispatcher->expects($this->exactly(1))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 [$request, $result],
             ]));

        $routeGroup = new RouteGroup(
            '/hello',
            static function (RouteGroup $group) use ($handler) : void {
                $group->map(
                    'GET',
                    '/world',
                    $handler,
                );
            },
        );

        $server = $this->mockHttpServer();

        $router = new Router($mockDispatcher, $routeGroup);

        $router->onStart($server);

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);
    }
}
