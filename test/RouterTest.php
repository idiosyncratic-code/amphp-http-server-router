<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Status;
use Amp\Promise;
use Error;
use League\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

class RouterTest extends TestCase
{
    public function testSuccessfulResponse() : void
    {
        $router = new Router();

        $router->map('GET', '/hello', new CallableRequestHandler(function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));

        $router->compileRoutes();

        $request = new Request(
            $this->createMock(Client::class),
            "GET",
            Uri\Http::createFromString("/hello")
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(200, $response->getStatus());
    }

    public function testRouteNotFoundResponse() : void
    {
        $router = new Router();

        $router->compileRoutes();

        $request = new Request(
            $this->createMock(Client::class),
            "GET",
            Uri\Http::createFromString("/hello")
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(404, $response->getStatus());
    }

    public function testMethodNotAllowedResponse() : void
    {
        $router = new Router();

        $router->map('GET', '/hello', new CallableRequestHandler(function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));

        $router->compileRoutes();

        $request = new Request(
            $this->createMock(Client::class),
            "DELETE",
            Uri\Http::createFromString("/hello")
        );

        $response = Promise\wait($router->handleRequest($request));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(405, $response->getStatus());

        $this->assertEquals('GET', $response->getHeader('Allow'));
    }

    public function testMappingRouteAfterCompilationFails() : void
    {
        $router = new Router();

        $router->compileRoutes();

        $this->expectException(Error::class);

        $router->map('GET', '/hello', new CallableRequestHandler(function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));
    }

    public function testMappingRouteWithEmptyStringFails() : void
    {
        $router = new Router();

        $this->expectException(Error::class);

        $router->map('', '/hello', new CallableRequestHandler(function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));
    }
}
