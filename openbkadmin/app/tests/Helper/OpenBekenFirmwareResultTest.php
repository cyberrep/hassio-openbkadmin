<?php

namespace Tests\OpenBKAdmin\Helper;

use PHPUnit\Framework\TestCase;
use OpenBKAdmin\Helper\OpenBekenFirmware;
use OpenBKAdmin\Helper\OpenBekenFirmwareResult;

class OpenBekenFirmwareResultTest extends TestCase
{
    public function testGettersReturnVersionDateAndFirmwares(): void
    {
        $publishedAt = new \DateTime('2026-06-11T00:00:00+00:00');
        $firmwares = [new OpenBekenFirmware('OpenBeken.bin', 'https://example.com/OpenBeken.bin')];
        $result = new OpenBekenFirmwareResult('v5.1.0', $publishedAt, $firmwares);

        self::assertSame('v5.1.0', $result->getVersion());
        self::assertSame($publishedAt, $result->getPublishDate());
        self::assertSame($firmwares, $result->getFirmwares());
    }
}
