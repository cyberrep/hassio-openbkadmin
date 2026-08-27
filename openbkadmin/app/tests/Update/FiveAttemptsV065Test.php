<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FiveAttemptsV065Test extends TestCase
{
    public function testUpdaterKeepsFiveStatusAttempts(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/js/device_update.js');
        self::assertStringContainsString('const defaultTries = 5;', $source);
    }
}
