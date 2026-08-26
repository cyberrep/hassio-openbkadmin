<?php

namespace Tests\OpenBKAdmin\Helper;

use PHPUnit\Framework\TestCase;
use OpenBKAdmin\Helper\OpenBekenFirmware;

class OpenBekenFirmwareTest extends TestCase
{
    public function testGettersReturnConfiguredValues(): void
    {
        $firmware = new OpenBekenFirmware('OpenBeken.bin', 'https://example.com/OpenBeken.bin');

        self::assertSame('OpenBeken.bin', $firmware->getName());
        self::assertSame('https://example.com/OpenBeken.bin', $firmware->getUrl());
    }
}
