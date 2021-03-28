<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

interface Dispatcher
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

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
