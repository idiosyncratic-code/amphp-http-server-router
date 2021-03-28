<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;

class TestResponseHandler implements RequestHandler
{
    public function handleRequest(Request $request) : Promise
    {
        return new Success(new Response(Status::OK));
    }
}
