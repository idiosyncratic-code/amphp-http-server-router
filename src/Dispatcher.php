<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;

interface Dispatcher
{
    public function dispatch(Request $request) : RouteHandler;

    /**
     * @param array<Route> $routes
     */
    public function setRoutes(array $routes) : void;
}
