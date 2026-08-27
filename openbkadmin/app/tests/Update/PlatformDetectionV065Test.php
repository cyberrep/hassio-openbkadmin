<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PlatformDetectionV065Test extends TestCase
{
    public function testUpdaterUsesDetectedPlatform(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/js/device_update.js');
        self::assertStringContainsString('detectDevicePlatform(response)', $source);
        self::assertStringContainsString('startOpenBekenOta(device.id, step.otaUrl, platform)', $source);
    }
}
