<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;

use function array_push;
use function array_unshift;
use function count;
use function ltrim;
use function sprintf;

final class RouteGroup
{
    private string $prefix;

    /** @var callable */
    private $callback;

    /** @var array<string | Middleware> */
    private array $middleware;

    /** @var array<Route> */
    private array $routes = [];

    /** @var array<RouteGroup> */
    private array $routeGroups = [];

    public function __construct(
        string $prefix,
        callable $callback,
        string | Middleware ...$middleware,
    ) {
        $this->prefix = $prefix;

        $this->callback = $callback;

        $this->middleware = $middleware;
    }

    public function map(
        string $method,
        string $path,
        string | RequestHandler $requestHandler,
        string | Middleware ...$middleware,
    ) : void {
        $path = $path === '/' ? $this->prefix : sprintf('%s/%s', $this->prefix, ltrim($path, '/'));

        array_unshift($middleware, ...$this->middleware);

        $this->routes[] = new Route($method, $path, $requestHandler, ...$middleware);
    }

    public function mapGroup(
        string $prefix,
        callable $callback,
        string | Middleware ...$middleware
    ) : void {
        $prefix = $prefix === '/' ? $this->prefix : sprintf('%s/%s', $this->prefix, ltrim($prefix, '/'));

        array_unshift($middleware, ...$this->middleware);

        $this->routeGroups[] = new RouteGroup($prefix, $callback, ...$middleware);
    }

    /**
     * @return array<Route>
     */
    public function getRoutes() : array
    {
        if (count($this->routes) === 0) {
            ($this->callback)($this);

            foreach ($this->routeGroups as $group) {
                array_push($this->routes, ...$group->getRoutes());
            }
        }

        return $this->routes;
    }
}
