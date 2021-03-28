<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\RequestHandler;
use PHPUnit\Framework\TestCase;

class CachingDispatcherTest extends TestCase
{
    public function testCachesDispatchResults() : void
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $mockDispatcher->expects($this->exactly(1))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 ['GET', '/hello', new DispatchResult($this->createMock(RequestHandler::class), [])],
             ]));

        $mockDispatcher->expects($this->once())
             ->method('compiled')
             ->will($this->returnValue(true));

        $mockDispatcher->expects($this->once())
             ->method('compile');

        $dispatcher = new CachingDispatcher($mockDispatcher);

        $dispatcher->dispatch('GET', '/hello');

        $dispatcher->dispatch('GET', '/hello');

        $dispatcher->compile([]);

        $this->assertTrue($dispatcher->compiled());
    }

    public function testCacheMaximumSizeIsEnforced() : void
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $result = new DispatchResult($this->createMock(RequestHandler::class), []);

        $mockDispatcher->expects($this->exactly(4))
             ->method('dispatch')
             ->will($this->returnValueMap([
                 ['GET', '/hello', $result],
                 ['GET', '/goodbye', $result],
             ]));

        $mockDispatcher->expects($this->once())
             ->method('compile');

        $dispatcher = new CachingDispatcher($mockDispatcher, 1);

        $dispatcher->compile([]);

        $dispatcher->dispatch('GET', '/hello');

        $dispatcher->dispatch('GET', '/hello');

        $dispatcher->dispatch('GET', '/goodbye');

        $dispatcher->dispatch('GET', '/goodbye');

        $dispatcher->dispatch('GET', '/hello');

        $dispatcher->dispatch('GET', '/goodbye');
    }
}
