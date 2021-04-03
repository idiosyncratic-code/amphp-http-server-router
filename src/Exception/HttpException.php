<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute\Exception;

interface HttpException
{
    public function getHttpStatusCode() : int;

    public function getHttpStatusReason() : string;
}
