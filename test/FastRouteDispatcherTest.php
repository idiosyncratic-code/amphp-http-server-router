<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Error;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FastRouteDispatcherTest extends TestCase
{
    public function testCompilingRoutesFailsIfCalledTwice() : void
    {
        $container = $this->createMock(ContainerInterface::class);

        $dispatcher = new FastRouteDispatcher($container);

        $dispatcher->compile([]);

        $this->expectException(Error::class);

        $dispatcher->compile([]);
    }
}
