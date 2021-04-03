<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute\Exception;

use RuntimeException;
use Throwable;

class MethodNotAllowed extends RuntimeException implements HttpException
{
    /** @var array<string> */
    private array $allow;

    /**
     * @param array<string> $allow
     */
    public function __construct(array $allow, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->allow = $allow;

        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode() : int
    {
        return 405;
    }

    public function getHttpStatusReason() : string
    {
        return 'Method Not Allowed';
    }

    /**
     * @return array<string>
     */
    public function getAllowedMethods() : array
    {
        return $this->allow;
    }
}
