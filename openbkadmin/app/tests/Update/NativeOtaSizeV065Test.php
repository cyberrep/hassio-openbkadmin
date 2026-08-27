<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaSizeV065Test extends TestCase
{
    public function testNativeOtaRejectsTinyFirmware(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString('$size < 512', $source);
    }
}
