<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute\Exception;

use RuntimeException;

class NotFound extends RuntimeException implements HttpException
{
    public function getHttpStatusCode() : int
    {
        return 404;
    }

    public function getHttpStatusReason() : string
    {
        return 'Not Found';
    }
}
