<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\ServerObserver;
use InvalidArgumentException;

use function array_filter;
use function sprintf;

final class Route
{
    private string $method;

    private string $path;

    private string | RequestHandler $requestHandler;

    /** @var array<string | Middleware> */
    private array $middleware;

    public function __construct(
        string $method,
        string $path,
        string | RequestHandler $requestHandler,
        string | Middleware ...$middleware,
    ) {
        $this->setMethod($method);

        $this->path = $path;

        $this->requestHandler = $requestHandler;

        $this->middleware = $middleware;
    }

    private function setMethod(string $method) : void
    {
        if ($method === '') {
            throw new InvalidArgumentException(
                sprintf('%s(): Argument #1 ($method) must be a non-empty string', __METHOD__)
            );
        }

        $this->method = $method;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getRequestHandler() : string | RequestHandler
    {
        return $this->requestHandler;
    }

    /**
     * @return array<string | Middleware>
     */
    public function getMiddleware() : array
    {
        return $this->middleware;
    }

    /**
     * @return array<ServerObserver>
     */
    public function getServerObservers() : array
    {
        $observers = array_filter($this->middleware, static function ($middleware) {
            return $middleware instanceof ServerObserver;
        });

        if ($this->requestHandler instanceof ServerObserver) {
            $observers[] = $this->requestHandler;
        }

        return $observers;
    }
}
