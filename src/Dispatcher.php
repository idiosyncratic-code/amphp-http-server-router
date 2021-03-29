<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Request;
use Amp\Http\Server\ServerObserver;

interface Dispatcher extends ServerObserver
{
    public function dispatch(Request $request) : DispatchResult;

    public function mapRoutes(RouteCollection $routes) : void;

    public function compile(RouteCollection $routes) : void;

    public function compiled() : bool;
}
