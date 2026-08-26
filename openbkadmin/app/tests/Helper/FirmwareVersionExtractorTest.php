<?php

namespace Tests\OpenBKAdmin\Helper;

use PHPUnit\Framework\TestCase;
use OpenBKAdmin\Helper\FirmwareVersionExtractor;

class FirmwareVersionExtractorTest extends TestCase
{
    public function testExtractsSemanticVersionFromFilename(): void
    {
        self::assertSame('14.4.1', FirmwareVersionExtractor::fromFilename('OpenBeken-custom-14.4.1.bin.gz'));
    }

    public function testExtractsFourPartVersionFromFilename(): void
    {
        self::assertSame('13.0.0.2', FirmwareVersionExtractor::fromFilename('OpenBeken32solo1-13.0.0.2.bin'));
    }

    public function testReturnsNullWhenFilenameDoesNotContainVersion(): void
    {
        self::assertNull(FirmwareVersionExtractor::fromFilename('my-custom-build.bin'));
    }
}
