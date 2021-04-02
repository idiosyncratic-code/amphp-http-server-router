<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use InvalidArgumentException;
use League\Uri;
use PHPUnit\Framework\TestCase;

class RouteHandlerTest extends TestCase
{
    public function testRouteArgumentsAreSet() : void
    {
        $routeHandler = new RouteHandler(new TestRequestHandler(), ['name' => 'world']);

        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Uri\Http::createFromString('/hello/world')
        );

        $response = Promise\wait($routeHandler->handleRequest($request));

        $body = Promise\wait($response->getBody()->read());

        $this->assertEquals(json_encode(['name' => 'world']), $body);
    }
}
