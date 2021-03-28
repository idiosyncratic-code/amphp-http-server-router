<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Error;

use function implode;
use function ltrim;

final class Router implements RequestHandler
{
    private Dispatcher $dispatcher;

    /** @var array<mixed> */
    private array $routes = [];

    public function __construct(
        Dispatcher $dispatcher,
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function map(
        string $method,
        string $uri,
        string | RequestHandler $requestHandler,
        string | Middleware ...$middlewares
    ) : void {
        if ($this->dispatcher->compiled() === true) {
            throw new Error('Routes already compiled');
        }

        if ($method === '') {
            throw new Error(
                __METHOD__ . '() requires a non-empty string HTTP method at Argument 1'
            );
        }

        $this->routes[] = [$method, ltrim($uri, '/'), ['handler' => $requestHandler, 'middleware' => $middlewares]];
    }

    public function handleRequest(Request $request) : Promise
    {
        $dispatched = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        // Ignore the next line because phpcs currently thinks the parentheses are "superfluous"
        // phpcs:ignore
        return match ($dispatched['status']) {
            Dispatcher::FOUND => $this->makeFoundResponse(
                $request,
                $dispatched['handler'],
                $dispatched['routeArgs'],
            ),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse(
                $request,
                $dispatched['allowedMethods'],
            ),
            // phpcs:ignore
            default => $this->makeNotFoundResponse($request),
        };
    }

    public function compileRoutes() : void
    {
        $this->dispatcher->compile($this->routes);
    }

    /**
     * @param array<mixed> $routeArgs
     *
     * @return Promise<Response>
     */
    private function makeFoundResponse(
        Request $request,
        RequestHandler $requestHandler,
        array $routeArgs,
    ) : Promise {
        $request->setAttribute(self::class, $routeArgs);

        return $requestHandler->handleRequest($request);
    }

    /**
     * @return Promise<Response>
     */
    private function makeNotFoundResponse(Request $request) : Promise
    {
        return new Success(new Response(Status::NOT_FOUND));
    }

    /**
     * @param array<string> $methods
     *
     * @return Promise<Response>
     */
    private function makeMethodNotAllowedResponse(
        Request $request,
        array $methods,
    ) : Promise {
        $response = new Response(Status::METHOD_NOT_ALLOWED);

        $response->setHeader('allow', implode(', ', $methods));

        return new Success($response);
    }
}
