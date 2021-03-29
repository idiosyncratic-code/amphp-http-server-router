<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware\CallableMiddleware;
use Amp\Http\Server\Middleware\CompressionMiddleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Error;
use InvalidArgumentException;
use League\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\Test\TestLogger;

class RouterTest extends TestCase
{
    use MocksHttpServer;
    use MocksHttpRequests;

    public function testFoundResponseWithCallableRequestHandler() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router(new FastRouteDispatcher($container));

        $router->map(
            'GET',
            '/hello',
            new CallableRequestHandler(static function () {
                return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
            }),
        );

        Promise\wait($router->onStart($this->mockServer()));

        $request = $this->mockRequest('GET', '/hello');

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(200, $response->getStatus());
    }

    public function testFoundResponseWithResponseHandlerFromContainer() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
             ->method('get')
             ->will($this->returnValue(new TestRequestHandler()));

        $router = new Router(new FastRouteDispatcher($container));

        $router->map('GET', '/hello/{name}', TestRequestHandler::class);

        Promise\wait($router->onStart($this->mockServer()));

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello/world')
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(200, $response->getStatus());
    }

    public function testFoundResponseWithCallableRequestHandlerAndMiddleware() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
             ->method('get')
             ->will($this->returnValue(new CompressionMiddleware()));

        $router = new Router(new FastRouteDispatcher($container));

        $router->map(
            'GET',
            '/hello',
            new CallableRequestHandler(static function () {
                return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
            }),
            new CallableMiddleware(static function ($request, $next) {
                $response = yield $next->handleRequest($request);
                $response->setHeader('x-foo-header', 'bar');

                return $response;
            }),
            new CallableMiddleware(static function ($request, $next) {
                $response = yield $next->handleRequest($request);
                $response->setHeader('x-bar-header', 'foo');

                return $response;
            }),
            CompressionMiddleware::class
        );

        Promise\wait($router->onStart($this->mockServer()));

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello')
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(200, $response->getStatus());

        $this->assertEquals('bar', $response->getHeader('x-foo-header'));

        $this->assertEquals('foo', $response->getHeader('x-bar-header'));
    }

    public function testRouteNotFoundResponse() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router(new FastRouteDispatcher($container));

        Promise\wait($router->onStart($this->mockServer()));

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello')
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(404, $response->getStatus());
    }

    public function testMethodNotAllowedResponse() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router(new FastRouteDispatcher($container));

        $router->map('GET', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));

        Promise\wait($router->onStart($this->mockServer()));

        $request = new Request(
            $this->createMock(Client::class),
            'DELETE',
            Uri\Http::createFromString('/hello')
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(405, $response->getStatus());

        $this->assertEquals('GET', $response->getHeader('Allow'));
    }

    public function testInvalidResponseHandlerResponse() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
             ->method('get')
             ->will($this->throwException(new ContainerEntryNotFound()));

        $router = new Router(new FastRouteDispatcher($container));

        $router->map('GET', '/hello/{name}', 'InvalidRequestHandler');

        Promise\wait($router->onStart($this->mockServer()));

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello/world')
        );

        $this->expectException(ContainerEntryNotFound::class);

        $router->handleRequest($request);
    }

    public function testMappingRouteAfterCompilationFails() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router(new FastRouteDispatcher($container));

        Promise\wait($router->onStart($this->mockServer()));

        $this->expectException(Error::class);

        $router->map('GET', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));
    }

    public function testMappingRouteWithEmptyStringFails() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router(new FastRouteDispatcher($container));

        $this->expectException(InvalidArgumentException::class);

        $router->map('', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));
    }

    public function testStartAndStopRouter() : void
    {
        $dispatcher = $this->createMock(Dispatcher::class);

        $dispatcher->method('onStart')
             ->will($this->returnValue(new Success()));

        $dispatcher->method('onStop')
             ->will($this->returnValue(new Success()));

        $router = new Router($dispatcher);

        $logger = new TestLogger();

        $mockServer = $this->mockServer($logger);

        Promise\wait($router->onStart($mockServer));

        $this->assertTrue($logger->hasRecordThatContains('Starting Router', 'debug'));

        Promise\wait($router->onStop($mockServer));

        $this->assertTrue($logger->hasRecordThatContains('Stopping Router', 'debug'));
    }

    public function testDoubleStartFails() : void
    {
        $dispatcher = $this->createMock(Dispatcher::class);

        $dispatcher->method('onStart')
             ->will($this->returnValue(new Success()));

        $router = new Router($dispatcher);

        $mockServer = $this->mockServer();

        Promise\wait($router->onStart($mockServer));

        $this->expectException(Error::class);

        Promise\wait($router->onStart($mockServer));
    }
}
