<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Failure;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\ServerObserver;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Error;
use Idiosyncratic\AmpRoute\Exception\MethodNotAllowed;
use Idiosyncratic\AmpRoute\Exception\NotFound;

use function implode;
use function ltrim;

final class Router implements RequestHandler, ServerObserver
{
    private Dispatcher $dispatcher;

    /** @var array<mixed> */
    private array $routes = [];

    private bool $running = false;

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
        try {
            return $this->dispatcher
                   ->dispatch($request->getMethod(), $request->getUri()->getPath())
                   ->handleRequest($request);
        } catch (MethodNotAllowed $t) {
            return $this->makeMethodNotAllowedResponse(
                $request,
                $t->getAllowedMethods(),
            );
        } catch (NotFound $t) {
            return $this->makeNotFoundResponse($request);
        }
    }

    private function compileRoutes() : void
    {
        $this->dispatcher->compile($this->routes);
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

    /**
     * @return Promise<mixed>
     */
    public function onStart(HttpServer $server): Promise
    {
        if ($this->running) {
            return new Failure(new Error('Router already started'));
        }

        $this->compileRoutes();

        $this->running = true;

        return new Success();
    }

    /**
     * @return Promise<mixed>
     */
    public function onStop(HttpServer $server): Promise
    {
        $this->running = false;

        return new Success();
    }
}
