<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Failure;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;
use Error;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function sprintf;

trait HasServerObservers
{
    /** @var SplObjectStorage<ServerObserver, null> */
    private SplObjectStorage $observers;

    private bool $running = false;

    private HttpServer $server;

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server) : Promise
    {
        if ($this->running) {
            return new Failure(new Error(sprintf('%s already started', static::class)));
        }

        $this->server = $server;

        $this->getLogger()->debug(sprintf('Starting %s', static::class));

        $this->running = true;

        $this->registerServerObservers(...$this->getServerObservers());

        return $this->startObservers($server);
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server) : Promise
    {
        $this->getLogger()->debug(sprintf('Starting %s', static::class));

        $this->running = false;

        return $this->stopObservers($server);
    }

    private function getLogger() : LoggerInterface
    {
        return $this->server->getLogger();
    }

    private function registerServerObservers(ServerObserver ...$observers) : void
    {
        $this->observers = new SplObjectStorage();

        foreach ($observers as $observer) {
            $this->observers->attach($observer);
        }
    }

    private function clearObservers() : void
    {
        /** @phpstan-ignore-next-line */
        $this->observers->removeAll($this->observers);
    }

    /**
     * @return Promise<mixed>
     */
    private function startObservers(HttpServer $server) : Promise
    {
        $promises = [];

        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStart($server);
        }

        /** @phpstan-ignore-next-line */
        return Promise\all($promises);
    }

    /**
     * @return Promise<mixed>
     */
    private function stopObservers(HttpServer $server) : Promise
    {
        $promises = [];

        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStop($server);
        }

        $this->clearObservers();

        /** @phpstan-ignore-next-line */
        return Promise\all($promises);
    }

    /**
     * @return array<ServerObserver>
     */
    abstract private function getServerObservers() : array;
}
