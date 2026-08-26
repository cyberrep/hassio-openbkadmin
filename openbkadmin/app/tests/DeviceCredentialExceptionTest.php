<?php

namespace Tests\OpenBKAdmin;

use PHPUnit\Framework\TestCase;
use OpenBKAdmin\DeviceCredentialException;

class DeviceCredentialExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new DeviceCredentialException('invalid credentials');

        self::assertInstanceOf(\RuntimeException::class, $exception);
        self::assertSame('invalid credentials', $exception->getMessage());
    }
}
