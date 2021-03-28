<?php

declare(strict_types=1);

namespace Idiosyncratic\Amp\Http\Server\Router;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ContainerEntryNotFound extends Exception implements NotFoundExceptionInterface
{
}
