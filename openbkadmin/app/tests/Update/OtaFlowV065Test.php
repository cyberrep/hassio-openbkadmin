<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OtaFlowV065Test extends TestCase
{
    public function testNonBlPlatformsStillUseOtaHttp(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/js/device_update.js');
        self::assertStringContainsString('return startCommandOta(deviceId, otaUrl);', $source);
        self::assertStringContainsString('`ota_http ${otaUrl}`', $source);
    }
}
