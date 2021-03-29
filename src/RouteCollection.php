<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Iterator;

use function current;
use function key;
use function next;
use function reset;

/**
 * @template-implements Iterator<Route>
 */
final class RouteCollection implements Iterator
{
    /** @var array<Route> */
    private array $data;

    public function __construct(Route ...$routes)
    {
        $this->data = $routes;
    }

    public function addRoute(Route $route) : void
    {
        $this->data[] = $route;
    }

    public function current() : Route
    {
        return current($this->data);
    }

    public function key() : mixed
    {
        return key($this->data);
    }

    public function next() : void
    {
        next($this->data);
    }

    public function rewind() : void
    {
        reset($this->data);
    }

    public function valid() : bool
    {
        return key($this->data) !== null;
    }
}
