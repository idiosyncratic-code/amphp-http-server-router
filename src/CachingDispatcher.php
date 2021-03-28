<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use function sprintf;

final class CachingDispatcher implements Dispatcher
{
    private Dispatcher $dispatcher;

    /** @var array<mixed> */
    private array $cache;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritdoc
     */
    public function dispatch(string $method, string $path) : array
    {
        $key = sprintf('%s--%s', $method, $path);

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->dispatcher->dispatch($method, $path);
    }

    /**
     * @inheritdoc
     */
    public function compile(array $routes) : void
    {
        $this->dispatcher->compile($routes);
    }

    public function compiled() : bool
    {
        return $this->dispatcher->compiled();
    }
}
