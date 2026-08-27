<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BackupRegressionV065Test extends TestCase
{
    public function testUpdatePageStillContainsPreOtaBackupFlow(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/device_update.php');
        self::assertStringContainsString('backup', strtolower($source));
    }
}
