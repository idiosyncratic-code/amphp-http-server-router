<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function array_pop;
use function array_shift;
use function count;

class RouteTest extends TestCase
{
    public function testCreateWithEmptyMethodFails() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $route = new Route(
            '',
            '/hello',
            $this->createMock(RequestHandler::class),
            $this->createMock(Middleware::class),
        );
    }

    public function testGetServerObservers() : void
    {
        $handler = new TestRequestHandlerObserver();
        $middleware1 = new TestMiddleware('x-foo', 'bar');
        $middleware2 = new TestMiddleware('x-bar', 'baz');
        $middleware3 = new TestMiddlewareObserver('x-baz', 'foo');

        $route = new Route(
            'GET',
            '/',
            $handler,
            $middleware1,
            $middleware2,
            $middleware3,
        );

        $this->assertEquals(2, count($route->getServerObservers()));
    }

    public function testAppendMiddleware() : void
    {
        $handler = new TestRequestHandlerObserver();
        $middleware1 = new TestMiddleware('x-foo', 'bar');
        $middleware2 = new TestMiddleware('x-bar', 'baz');
        $middleware3 = new TestMiddlewareObserver('x-baz', 'foo');

        $route1 = new Route(
            'GET',
            '/',
            $handler,
            $middleware1,
        );

        $route2 = $route1->appendMiddleware($middleware2, $middleware3);

        $this->assertFalse($route1 === $route2);

        $this->assertEquals(1, count($route1->getMiddleware()));

        $this->assertEquals(3, count($route2->getMiddleware()));

        $this->assertEquals($route1->getPath(), $route2->getPath());

        $this->assertEquals($route1->getMethod(), $route2->getMethod());

        $this->assertEquals($route1->getRequestHandler(), $route2->getRequestHandler());

        $middlewareStack = $route2->getMiddleware();

        $this->assertSame($middleware3, array_pop($middlewareStack));
    }

    public function testPrependMiddleware() : void
    {
        $handler = new TestRequestHandlerObserver();
        $middleware1 = new TestMiddleware('x-foo', 'bar');
        $middleware2 = new TestMiddleware('x-bar', 'baz');
        $middleware3 = new TestMiddlewareObserver('x-baz', 'foo');

        $route1 = new Route(
            'GET',
            '/',
            $handler,
            $middleware1,
        );

        $route2 = $route1->prependMiddleware($middleware2, $middleware3);

        $this->assertFalse($route1 === $route2);

        $this->assertEquals(1, count($route1->getMiddleware()));

        $this->assertEquals(3, count($route2->getMiddleware()));

        $middlewareStack = $route2->getMiddleware();

        $this->assertSame($middleware2, array_shift($middlewareStack));
    }
}
