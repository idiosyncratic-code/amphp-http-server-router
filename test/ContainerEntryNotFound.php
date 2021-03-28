<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ContainerEntryNotFound extends Exception implements NotFoundExceptionInterface
{
}
