<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Options;
use Amp\Http\Server\RequestHandler;
use Amp\Socket;
use Psr\Log\LoggerInterface;

trait MocksHttpServer
{
    protected function mockServer(): HttpServer
    {
        $options = new Options();

        $socket = Socket\listen('127.0.0.1:0');

        return new HttpServer(
            [$socket],
            $this->createMock(RequestHandler::class),
            $this->createMock(LoggerInterface::class),
            $options
        );
    }
}
