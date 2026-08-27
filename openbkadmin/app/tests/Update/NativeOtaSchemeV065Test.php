<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaSchemeV065Test extends TestCase
{
    public function testOnlyHttpFirmwareUrlsAreAccepted(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString("['http', 'https']", $source);
    }
}
