<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaMethodV065Test extends TestCase
{
    public function testNativeOtaPostsBinaryFirmware(): void
    {
        $actions = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString("request('POST', \$otaEndpoint", $actions);
        self::assertStringContainsString("'Content-Type' => 'application/octet-stream'", $actions);
    }
}
