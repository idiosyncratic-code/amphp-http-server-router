<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Promise;
use Error;
use League\Uri;
use PHPUnit\Framework\TestCase;

class MiddlewareRequestHandlerTest extends TestCase
{
    use MocksHttpServer;

    public function testHandlesRequests() : void
    {
        $requestHandler = new TestRequestHandlerObserver();
        $middleware1 = new TestMiddleware('x-foo', 'bar');
        $middleware2 = new TestMiddlewareObserver('x-baz', 'foo');

        $middlewareHandler = new MiddlewareRequestHandler($requestHandler, $middleware1, $middleware2);

        $server = $this->mockHttpServer();

        $middlewareHandler->onStart($server);

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello/world')
        );

        for ($i = 0; $i < 2; $i++) {
            $response = Promise\wait($middlewareHandler->handleRequest($request));

            $this->assertEquals('bar', $response->getHeader('x-foo'));

            $this->assertEquals('foo', $response->getHeader('x-baz'));
        }

        $middlewareHandler->onStop($server);
    }

    public function testDoubleStartFails() : void
    {
        $requestHandler = new TestRequestHandlerObserver();
        $middleware1 = new TestMiddleware('x-foo', 'bar');
        $middleware2 = new TestMiddlewareObserver('x-baz', 'foo');

        $middlewareHandler = new MiddlewareRequestHandler($requestHandler, $middleware1, $middleware2);

        $server = $this->mockHttpServer();

        $middlewareHandler->onStart($server);

        $this->expectException(Error::class);

        Promise\wait($middlewareHandler->onStart($server));
    }
}
