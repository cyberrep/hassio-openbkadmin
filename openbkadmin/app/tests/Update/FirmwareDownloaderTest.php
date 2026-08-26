<?php

namespace Tests\OpenBKAdmin\Update;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use OpenBKAdmin\Update\FirmwareDownloader;

class FirmwareDownloaderTest extends TestCase
{
    public function testDownloadUsesSinkPathBasedOnFilename(): void
    {
        $client = $this->createMock(Client::class);
        $downloader = new FirmwareDownloader($client, '/tmp/downloads/');

        $client->expects(self::once())
            ->method('get')
            ->with(
                'https://example.com/firmware/OpenBeken.bin',
                ['sink' => '/tmp/downloads/OpenBeken.bin']
            )
        ;

        self::assertSame(
            '/tmp/downloads/OpenBeken.bin',
            $downloader->download('https://example.com/firmware/OpenBeken.bin')
        );
    }
}
