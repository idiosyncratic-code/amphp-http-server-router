<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Error;
use League\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RouterTest extends TestCase
{
    public function testSuccessfulResponse() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router($container);

        $router->map('GET', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));

        $router->compileRoutes();

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello')
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(200, $response->getStatus());
    }

    public function testRouteNotFoundResponse() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router($container);

        $router->compileRoutes();

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

        $router = new Router($container);

        $router->map('GET', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));

        $router->compileRoutes();

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

        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testMappingRouteAfterCompilationFails() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router($container);

        $router->compileRoutes();

        $this->expectException(Error::class);

        $router->map('GET', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));
    }

    public function testMappingRouteWithEmptyStringFails() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $router = new Router($container);

        $this->expectException(Error::class);

        $router->map('', '/hello', new CallableRequestHandler(static function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));
    }
}
