<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class VersionV065ReadmeTest extends TestCase
{
    public function testReadmeShowsCurrentVersion(): void
    {
        $readme = file_get_contents(__DIR__.'/../../../../README.md');
        self::assertStringContainsString('Current add-on version: **0.6.5**', $readme);
        self::assertStringContainsString('### 0.6.5', $readme);
    }
}
