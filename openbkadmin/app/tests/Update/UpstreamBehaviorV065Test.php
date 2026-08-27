<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UpstreamBehaviorV065Test extends TestCase
{
    public function testImplementationNotesCoverStateAndBl602(): void
    {
        $notes = file_get_contents(__DIR__.'/../fixtures/README-v065.txt');
        self::assertStringContainsString('CHANNEL_Get()', $notes);
        self::assertStringContainsString('POST /api/ota', $notes);
        self::assertStringContainsString('BL60X_OTA', $notes);
    }
}
