<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\ServerObserver;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;

class TestRequestHandlerObserver implements RequestHandler, ServerObserver
{
    public function handleRequest(Request $request) : Promise
    {
        return new Success(new Response(Status::OK));
    }

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server): Promise
    {
        return new Success();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server): Promise
    {
        return new Success();
    }
}
