<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

interface Dispatcher
{
    /**
     * @return array<mixed>
     */
    public function dispatch(string $method, string $path) : array;

    /**
     * @param array<mixed> $routes
     */
    public function compile(array $routes) : void;

    public function compiled() : bool;
}
