<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Options;
use Amp\Http\Server\RequestHandler;
use Amp\Socket;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

trait MocksHttpServer
{
    protected function mockServer(?LoggerInterface $logger = null) : HttpServer
    {
        $logger ??= new TestLogger();

        $options = new Options();

        $socket = Socket\listen('127.0.0.1:0');

        return new HttpServer(
            [$socket],
            $this->createMock(RequestHandler::class),
            $logger,
            $options,
        );
    }
}
