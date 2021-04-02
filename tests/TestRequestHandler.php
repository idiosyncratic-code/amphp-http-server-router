<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;

use function Amp\call;

use const JSON_THROW_ON_ERROR;

class TestRequestHandler implements RequestHandler
{
    public function handleRequest(Request $request) : Promise
    {
        return call(static function () use ($request) {
            $body = $request->hasAttribute(Router::class) ?
                json_encode($request->getAttribute(Router::class), JSON_THROW_ON_ERROR) :
                '';

            return new Response(code: Status::OK, stringOrStream: $body);
        });
    }
}
