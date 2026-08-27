<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaAuthV065Test extends TestCase
{
    public function testNativeOtaUsesConfiguredDeviceCredentials(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString('RequestOptions::AUTH', $source);
        self::assertStringContainsString('$device->username', $source);
        self::assertStringContainsString('$device->password', $source);
    }
}
