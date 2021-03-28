<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use PHPUnit\Framework\TestCase;

class CachingDispatcherTest extends TestCase
{
    public function testCachesDispatchResults() : void
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $mockDispatcher->expects($this->once())
             ->method('dispatch')
             ->will($this->returnValue(new DispatchResult($this->createMock(RequestHandler::class), [])));

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
}
