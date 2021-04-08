<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;
use SplObjectStorage;

trait HasServerObservers
{
    /** @var SplObjectStorage<ServerObserver, null> */
    private SplObjectStorage $observers;

    private function addObserver(ServerObserver $observer) : void
    {
        $this->observers->attach($observer);
    }

    private function setupObservers() : void
    {
        $this->observers = new SplObjectStorage();
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
}
