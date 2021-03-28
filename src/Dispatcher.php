<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\ServerObserver;

interface Dispatcher extends ServerObserver
{
    public function dispatch(string $method, string $path) : DispatchResult;

    /**
     * @param array<mixed> $routes
     */
    public function compile(array $routes) : void;

    public function compiled() : bool;
}
