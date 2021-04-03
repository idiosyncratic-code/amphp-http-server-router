<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute\Exception;

use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testNotFoundException() : void
    {
        $exception = new NotFound();

        $this->assertEquals(404, $exception->getHttpStatusCode());

        $this->assertEquals('Not Found', $exception->getHttpStatusReason());
    }

    public function testMethodNotAllowedException() : void
    {
        $exception = new MethodNotAllowed(['GET', 'POST']);

        $this->assertEquals(405, $exception->getHttpStatusCode());

        $this->assertEquals('Method Not Allowed', $exception->getHttpStatusReason());

        $this->assertEquals(['GET', 'POST'], $exception->getAllowedMethods());
    }

    public function testInternalServerErrorException() : void
    {
        $exception = new InternalServerError();

        $this->assertEquals(500, $exception->getHttpStatusCode());

        $this->assertEquals('Internal Server Error', $exception->getHttpStatusReason());
    }
}
