<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Error;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;
use function implode;
use function is_string;
use function ltrim;
use function sprintf;

final class Router implements RequestHandler
{
    /** @var array<mixed> */
    private array $routes = [];

    private Dispatcher $dispatcher;

    private bool $compiled = false;

    public function map(
        string $method,
        string $uri,
        string | RequestHandler $requestHandler
    ) : void {
        if ($this->compiled === true) {
            throw new Error('Routes already compiled');
        }

        if ($method === '') {
            throw new Error(
                __METHOD__ . '() requires a non-empty string HTTP method at Argument 1'
            );
        }

        $this->routes[] = [$method, ltrim($uri, '/'), $requestHandler];
    }

    public function handleRequest(Request $request) : Promise
    {
        $method = $request->getMethod();

        $path = $request->getUri()->getPath();
        //$path = rawurldecode($request->getUri()->getPath());

        $dispatched = $this->dispatcher->dispatch($method, $path);

        return match ($dispatched[0]) {
            Dispatcher::FOUND => $this->makeFoundResponse($request, $dispatched[1], $dispatched[2]),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse($request, $dispatched[1]),
        default => $this->makeNotFoundResponse($request),
        };
    }

    /**
     * @param array<mixed> $routeArgs
     *
     * @return Promise<Response>
     */
    private function makeFoundResponse(
        Request $request,
        string | RequestHandler $requestHandler,
        array $routeArgs,
    ) : Promise {
        $request->setAttribute(self::class, $routeArgs);

        if (is_string($requestHandler)) {
            throw new Error('String Request Handlers are not implemented yet');
        }

        return $requestHandler->handleRequest($request);
    }

    /**
     * @return Promise<Response>
     */
    private function makeNotFoundResponse(
        Request $request
    ) : Promise {
        return new Success(new Response(Status::NOT_FOUND));
    }

    /**
     * @param array<string> $methods
     *
     * @return Promise<Response>
     */
    private function makeMethodNotAllowedResponse(
        Request $request,
        array $methods
    ) : Promise {
        $response = new Response(Status::METHOD_NOT_ALLOWED);

        $response->setHeader('allow', implode(', ', $methods));

        return new Success($response);
    }

    public function compileRoutes() : void
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $collector): void {
            foreach ($this->routes as [$method, $uri, $requestHandler]) {
                $uri = sprintf('/%s', $uri);

                $collector->addRoute($method, $uri, $requestHandler);
            }
        });

        $this->compiled = true;
    }
}
