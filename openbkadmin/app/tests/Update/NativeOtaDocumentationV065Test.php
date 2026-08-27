<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NativeOtaDocumentationV065Test extends TestCase
{
    public function testAddonVersionAndChangelogAreUpdated(): void
    {
        $config = file_get_contents(__DIR__.'/../../../config.yaml');
        $changelog = file_get_contents(__DIR__.'/../../../CHANGELOG.md');
        self::assertStringContainsString('version: "0.6.5"', $config);
        self::assertStringContainsString('## [0.6.5]', $changelog);
        self::assertStringContainsString('POST /api/ota', $changelog);
    }
}
