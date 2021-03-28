<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute\Exception;

use RuntimeException;
use Throwable;

class MethodNotAllowed extends RuntimeException
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

    /**
     * @return array<string>
     */
    public function getAllowedMethods() : array
    {
        return $this->allow;
    }
}
