<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Promise;

use function array_shift;
use function count;
use function sprintf;

final class CachingDispatcher implements Dispatcher
{
    private Dispatcher $dispatcher;

    /** @var array<mixed> */
    private array $cache = [];

    private int $cacheSize;

    private HttpServer $server;

    public function __construct(Dispatcher $dispatcher, int $cacheSize = 512)
    {
        $this->dispatcher = $dispatcher;

        $this->cacheSize = $cacheSize;
    }

    public function dispatch(Request $request) : DispatchResult
    {
        $key = sprintf('%s--%s', $request->getMethod(), $request->getUri()->getPath());

        if ($this->has($key)) {
            return $this->get($key);
        }

        return $this->put($key, $this->dispatcher->dispatch($request));
    }

    public function compile(RouteCollection $routes) : void
    {
        $this->dispatcher->compile($routes);
    }

    public function mapRoutes(RouteCollection $routes) : void
    {
        $this->dispatcher->mapRoutes($routes);
    }

    public function compiled() : bool
    {
        return $this->dispatcher->compiled();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server) : Promise
    {
        $this->server = $server;

        return $this->dispatcher->onStart($server);
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        unset($this->server);

        $this->cache = [];

        return $this->dispatcher->onStop($server);
    }

    private function has(string $key) : bool
    {
        return isset($this->cache[$key]);
    }

    private function get(string $key) : DispatchResult
    {
        return $this->put($key, $this->cache[$key]);
    }

    private function remove(string $key) : void
    {
        if ($this->has($key) === false) {
            return;
        }

        unset($this->cache[$key]);
    }

    private function put(string $key, DispatchResult $result) : DispatchResult
    {
        $this->remove($key);

        $this->cache[$key] = $result;

        if (count($this->cache) > $this->cacheSize) {
            array_shift($this->cache);
        }

        return $result;
    }
}
