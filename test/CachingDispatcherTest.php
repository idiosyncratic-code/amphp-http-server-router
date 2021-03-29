<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\RequestHandler;
use PHPUnit\Framework\TestCase;

class CachingDispatcherTest extends TestCase
{
    use MocksHttpRequests;

    public function testCachesDispatchResults() : void
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $request = $this->mockRequest('GET', '/hello');

        $mockDispatcher->expects($this->exactly(1))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 [$request, new DispatchResult($this->createMock(RequestHandler::class), [])],
             ]));

        $mockDispatcher->expects($this->once())
             ->method('compiled')
             ->will($this->returnValue(true));

        $mockDispatcher->expects($this->once())
             ->method('compile');

        $dispatcher = new CachingDispatcher($mockDispatcher);

        $dispatcher->dispatch($request);

        $dispatcher->dispatch($request);

        $dispatcher->compile(new RouteCollection());

        $this->assertTrue($dispatcher->compiled());
    }

    public function testCacheMaximumSizeIsEnforced() : void
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $result = new DispatchResult($this->createMock(RequestHandler::class), []);

        $helloRequest = $this->mockRequest('GET', '/hello');

        $goodbyeRequest = $this->mockRequest('GET', '/goodbye');

        $mockDispatcher->expects($this->exactly(4))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 [$helloRequest, $result],
                 [$goodbyeRequest, $result],
             ]));

        $mockDispatcher->expects($this->once())
             ->method('compile');

        $dispatcher = new CachingDispatcher($mockDispatcher, 1);

        $dispatcher->compile(new RouteCollection());

        $dispatcher->dispatch($helloRequest);

        $dispatcher->dispatch($helloRequest);

        $dispatcher->dispatch($goodbyeRequest);

        $dispatcher->dispatch($goodbyeRequest);

        $dispatcher->dispatch($helloRequest);

        $dispatcher->dispatch($goodbyeRequest);
    }
}
