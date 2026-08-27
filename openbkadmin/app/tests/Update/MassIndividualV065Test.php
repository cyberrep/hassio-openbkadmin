<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MassIndividualV065Test extends TestCase
{
    public function testUpdaterKeepsBothExecutionModes(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/js/device_update.js');
        self::assertStringContainsString('mode === "individual"', $source);
        self::assertStringContainsString('Promise.all(devices.map', $source);
    }
}
