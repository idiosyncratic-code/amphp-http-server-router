<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use Amp\Promise;

interface Dispatcher
{
    /**
     * @return Promise<RouteHandler>
     */
    public function dispatch(Request $request) : Promise;

    public function setRoutes(RouteGroup $routes) : void;
}
