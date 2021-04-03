<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute\Exception;

use RuntimeException;

class InternalServerError extends RuntimeException implements HttpException
{
    public function getHttpStatusCode() : int
    {
        return 500;
    }

    public function getHttpStatusReason() : string
    {
        return 'Internal Server Error';
    }
}
