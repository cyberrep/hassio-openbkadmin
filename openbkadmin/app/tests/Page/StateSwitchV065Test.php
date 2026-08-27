<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StateSwitchV065Test extends TestCase
{
    public function testEveryDeviceRowStillRendersStateSwitch(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/elements/devices_table.php');
        self::assertStringContainsString("<td class='status'", $source);
        self::assertStringContainsString('<label class="form-switch">', $source);
        self::assertStringContainsString('<input type="checkbox">', $source);
    }
}
