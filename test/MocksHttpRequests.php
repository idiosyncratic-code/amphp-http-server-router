<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use League\Uri;

trait MocksHttpRequests
{
    protected function mockRequest(string $method, string $path) : Request
    {
        return new Request(
            $this->createMock(Client::class),
            $method,
            Uri\Http::createFromString($path)
        );
    }
}
