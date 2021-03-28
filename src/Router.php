<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

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
use Idiosyncratic\Amp\Http\Server\Router\Exception\MethodNotAllowed;
use Idiosyncratic\Amp\Http\Server\Router\Exception\NotFound;

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

    /*
    public function onStart(Server $server): Promise
    {
        if ($this->running) {
            return new Failure(new \Error("Router already started"));
        }

        if (empty($this->routes)) {
            return new Failure(new \Error(
                "Router start failure: no routes registered"
            ));
        }

        $this->running = true;

        $options = $server->getOptions();
        $allowedMethods = $options->getAllowedMethods();
        $logger = $server->getLogger();

        $this->routeDispatcher = simpleDispatcher(function (RouteCollector $rc) use ($allowedMethods, $logger) {
            foreach ($this->routes as list($method, $uri, $requestHandler)) {
                if (!\in_array($method, $allowedMethods, true)) {
                    $logger->alert(
                        "Router URI '$uri' uses method '$method' that is not in the list of allowed methods"
                    );
                }

                $requestHandler = Middleware\stack($requestHandler, ...$this->middlewares);
                $uri = $this->prefix . $uri;

                // Special-case, otherwise we redirect just to the same URI again
                if ($uri === "/?") {
                    $uri = "/";
                }

                if (\substr($uri, -2) === "/?") {
                    $canonicalUri = \substr($uri, 0, -2);
                    $redirectUri = \substr($uri, 0, -1);

                    $rc->addRoute($method, $canonicalUri, $requestHandler);
                    $rc->addRoute($method, $redirectUri, new CallableRequestHandler(static function (Request $request): Response {
                        $uri = $request->getUri();
                        $path = \rtrim($uri->getPath(), '/');

                        if ($uri->getQuery() !== "") {
                            $redirectTo = $path . "?" . $uri->getQuery();
                        } else {
                            $redirectTo = $path;
                        }

                        return new Response(Status::PERMANENT_REDIRECT, [
                            "location" => $redirectTo,
                            "content-type" => "text/plain; charset=utf-8",
                        ], "Canonical resource location: {$path}");
                    }));
                } else {
                    $rc->addRoute($method, $uri, $requestHandler);
                }
            }
        });

        $this->errorHandler = $server->getErrorHandler();

        if ($this->fallback instanceof ServerObserver) {
            $this->observers->attach($this->fallback);
        }

        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof ServerObserver) {
                $this->observers->attach($middleware);
            }
        }

        $promises = [];
        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStart($server);
        }

        return Promise\all($promises);
    }

    public function onStop(Server $server): Promise
    {
        $this->routeDispatcher = null;
        $this->running = false;

        $promises = [];
        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStop($server);
        }

        return Promise\all($promises);
    }

     */
}
