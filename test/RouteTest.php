<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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
}
