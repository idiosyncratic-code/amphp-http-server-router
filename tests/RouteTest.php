<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

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

        $this->assertEquals('GET', $route->getMethod());
    }

    public function testResolveRequestHandler() : void
    {
        $container = $this->createMock(ContainerInterface::class);

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

        $resolvedHandler = $route->resolveRequestHandler($container);

        $this->assertInstanceOf(RequestHandler::class, $resolvedHandler);
    }

    public function testResolveRequestHandlerDependencyUsingContainer() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(1))
             ->method('get')
             ->will($this->returnValueMap([
                 [TestRequestHandler::class, new TestRequestHandler()],
             ]));

        $route = new Route(
            'GET',
            '/',
            TestRequestHandler::class,
        );

        $resolvedHandler = $route->resolveRequestHandler($container);

        $this->assertInstanceOf(RequestHandler::class, $resolvedHandler);
    }

    public function testResolveMiddlewareDependencyUsingContainer() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(1))
             ->method('get')
             ->will($this->returnValueMap([
                 [TestMiddleware::class, new TestMiddleware()],
             ]));

        $route = new Route(
            'GET',
            '/',
            new TestRequestHandler(),
            TestMiddleware::class,
        );

        $resolvedHandler = $route->resolveRequestHandler($container);

        $this->assertInstanceOf(RequestHandler::class, $resolvedHandler);
    }

    public function testResolveDependenciesUsingContainerFails() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(1))
             ->method('get')
              ->will($this->throwException(new ContainerEntryNotFound()));

        $route = new Route(
            'GET',
            '/',
            TestRequestHandler::class,
        );

        $this->expectException(Exception\InternalServerError::class);

        $route->resolveRequestHandler($container);
    }
}
