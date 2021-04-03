<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\RequestHandler;
use PHPUnit\Framework\TestCase;

class CachingDispatcherTest extends TestCase
{
    use MocksHttpRequests;
    use MocksHttpServer;

    public function testCachesRouteHandlers() : void
    {
        $mockDispatcher = $this->createMock(TestServerObserverDispatcher::class);

        $request = $this->mockRequest('GET', '/hello');

        $mockDispatcher->expects($this->exactly(1))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 [$request, new RouteHandler($this->createMock(RequestHandler::class), [])],
             ]));

        $mockDispatcher->expects($this->once())
             ->method('setRoutes');

        $mockDispatcher->expects($this->once())
             ->method('onStart');

        $mockDispatcher->expects($this->once())
             ->method('onStop');

        $mockHttpServer = $this->mockHttpServer();

        $dispatcher = new CachingDispatcher($mockDispatcher);

        $dispatcher->onStart($mockHttpServer);

        $dispatcher->dispatch($request);

        $dispatcher->dispatch($request);

        $dispatcher->setRoutes([]);

        $dispatcher->onStop($mockHttpServer);
    }

    public function testCacheMaximumSizeIsEnforced() : void
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $result = new RouteHandler($this->createMock(RequestHandler::class), []);

        $helloRequest = $this->mockRequest('GET', '/hello');

        $goodbyeRequest = $this->mockRequest('GET', '/goodbye');

        $mockDispatcher->expects($this->exactly(4))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 [$helloRequest, $result],
                 [$goodbyeRequest, $result],
             ]));

        $mockHttpServer = $this->mockHttpServer();

        $dispatcher = new CachingDispatcher($mockDispatcher, 1);

        $dispatcher->onStart($mockHttpServer);

        $dispatcher->setRoutes([]);

        $dispatcher->dispatch($helloRequest);

        $dispatcher->dispatch($helloRequest);

        $dispatcher->dispatch($goodbyeRequest);

        $dispatcher->dispatch($goodbyeRequest);

        $dispatcher->dispatch($helloRequest);

        $dispatcher->dispatch($goodbyeRequest);

        $dispatcher->onStop($mockHttpServer);
    }
}
