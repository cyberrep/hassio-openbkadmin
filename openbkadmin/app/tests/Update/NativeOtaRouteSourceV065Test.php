<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaRouteSourceV065Test extends TestCase
{
    public function testBl602UsesNativeRouteAndOtherPlatformsKeepOtaHttp(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/js/device_update.js');
        self::assertStringContainsString('platform === "BL602" || platform === "BL616"', $source);
        self::assertStringContainsString('startNativeWebAppOta', $source);
        self::assertStringContainsString('`ota_http ${otaUrl}`', $source);
    }
}
