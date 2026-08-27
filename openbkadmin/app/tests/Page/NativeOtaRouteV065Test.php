<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaRouteV065Test extends TestCase
{
    public function testActionsContainsNativeOtaEndpoint(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString("isset(\$_GET['nativeOta'])", $source);
        self::assertStringContainsString("/api/ota", $source);
        self::assertStringContainsString("application/octet-stream", $source);
    }

    public function testUpdaterRoutesBl602AndBl616ToNativeOta(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/js/device_update.js');
        self::assertStringContainsString('platform === "BL602"', $source);
        self::assertStringContainsString('platform === "BL616"', $source);
        self::assertStringContainsString('actions?nativeOta', $source);
    }
}
