# AmpRoute: A flexible HTTP Router for Amp

## Usage

```
<?php

require_once('path/to/vendor/autoload.php');

use Idiosyncratic\AmpRoute\CachingDispatcher;
use Idiosyncratic\AmpRoute\FastRouteDispatcher;
use Idiosyncratic\AmpRoute\Router;
use PsrContainerImplementation;

$dispatcher = new CachingDispatcher(
    new FastRouteDispatcher(
        new PsrContainerImplementation()
    )
);

$router = new Router($dispatcher);

$router->map('GET', '/hello/{name}', TestResponseHandler::class);

```
