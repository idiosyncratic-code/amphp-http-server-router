<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\RequestHandler;

interface Dispatcher
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    /**
     * @return array<mixed>
     */
    public function dispatch(string $method, string $path) : RequestHandler;

    /**
     * @param array<mixed> $routes
     */
    public function compile(array $routes) : void;

    public function compiled() : bool;
}
