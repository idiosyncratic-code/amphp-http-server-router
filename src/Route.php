<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\ServerObserver;
use Idiosyncratic\AmpRoute\Exception\InternalServerError;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Throwable;

use function array_filter;
use function array_map;
use function count;
use function is_string;
use function ltrim;
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

        $path = sprintf('/%s', ltrim($path, '/'));

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

    public function resolveRequestHandler(ContainerInterface $container) : RequestHandler
    {
        try {
            if (is_string($this->requestHandler)) {
                $requestHandler = $container->get($this->requestHandler);
            } else {
                $requestHandler = $this->requestHandler;
            }

            if (count($this->middleware) === 0) {
                return $requestHandler;
            }

            $middleware = array_map(static function ($item) use ($container) {
                return $item instanceof Middleware ?
                    $item :
                    $container->get($item);
            }, $this->middleware);

            return new MiddlewareRequestHandler($requestHandler, ...$middleware);
        } catch (Throwable $t) {
            throw new InternalServerError(previous: $t);
        }
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
