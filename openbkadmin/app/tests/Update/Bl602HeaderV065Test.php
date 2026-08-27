<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class Bl602HeaderV065Test extends TestCase
{
    public function testNotesReferenceUpstreamBl602Header(): void
    {
        $notes = file_get_contents(__DIR__.'/../fixtures/README-v065.txt');
        self::assertStringContainsString('BL60X_OTA', $notes);
    }
}
