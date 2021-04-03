<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;
use Amp\Success;

use function array_shift;
use function count;
use function sprintf;

final class CachingDispatcher implements Dispatcher, ServerObserver
{
    private Dispatcher $dispatcher;

    /** @var array<RouteHandler> */
    private array $cache = [];

    private int $cacheSize;

    private HttpServer $server;

    public function __construct(Dispatcher $dispatcher, int $cacheSize = 512)
    {
        $this->dispatcher = $dispatcher;

        $this->cacheSize = $cacheSize;
    }

    public function dispatch(Request $request) : RouteHandler
    {
        $key = sprintf('%s--%s', $request->getMethod(), $request->getUri()->getPath());

        if ($this->has($key)) {
            return $this->get($key);
        }

        return $this->put($key, $this->dispatcher->dispatch($request));
    }

    /**
     * @inheritdoc
     */
    public function setRoutes(array $routes) : void
    {
        $this->clear();

        $this->dispatcher->setRoutes($routes);
    }

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server) : Promise
    {
        $this->server = $server;

        if ($this->dispatcher instanceof ServerObserver) {
            return $this->dispatcher->onStart($server);
        }

        return new Success();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        unset($this->server);

        $this->clear();

        if ($this->dispatcher instanceof ServerObserver) {
            return $this->dispatcher->onStop($server);
        }

        return new Success();
    }

    private function has(string $key) : bool
    {
        return isset($this->cache[$key]);
    }

    private function get(string $key) : RouteHandler
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

    private function clear() : void
    {
        $this->cache = [];
    }

    private function put(string $key, RouteHandler $result) : RouteHandler
    {
        $this->remove($key);

        $this->cache[$key] = $result;

        if (count($this->cache) > $this->cacheSize) {
            array_shift($this->cache);
        }

        return $result;
    }
}
