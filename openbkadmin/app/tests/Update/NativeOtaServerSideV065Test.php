<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaServerSideV065Test extends TestCase
{
    public function testActionsDownloadsAndPostsFirmwareServerSide(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString("request('GET', \$firmwareUrl", $source);
        self::assertStringContainsString("request('POST', \$otaEndpoint", $source);
    }
}
