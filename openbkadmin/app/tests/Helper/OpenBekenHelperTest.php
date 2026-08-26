<?php

namespace Tests\OpenBKAdmin\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use OpenBKAdmin\Helper\OpenBekenFirmware;
use OpenBKAdmin\Helper\OpenBekenFirmwareResult;
use OpenBKAdmin\Helper\OpenBekenHelper;
use OpenBKAdmin\Helper\OpenBekenOtaScraper;
use Tests\OpenBKAdmin\TestUtils;

class OpenBekenHelperTest extends TestCase
{
    public function testGetEsp8266ReleasesExcludesEsp32Assets(): void
    {
        $helper = $this->createHelper();

        self::assertContains('OpenBeken-sensors', $helper->getEsp8266Releases());
        self::assertNotContains('OpenBeken32', $helper->getEsp8266Releases());
    }

    public function testGetEsp32ReleasesExcludesEsp8266Assets(): void
    {
        $helper = $this->createHelper();

        self::assertContains('OpenBeken32', $helper->getEsp32Releases());
        self::assertNotContains('OpenBeken-sensors', $helper->getEsp32Releases());
    }

    public function testGetReleaseNotesTransformsContentAndIssueLinks(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('get')
            ->with(self::stringContains('RELEASENOTES.md?r='))
            ->willReturn(new Response(200, [], "/*\n * Fixes #123\n */\n![logo](https://github.com/arendst/OpenBeken/blob/master/tools/logo/OpenBeken_FullLogo_Vector.svg)\n"))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $client,
            $this->createMock(OpenBekenOtaScraper::class),
            'stable'
        );

        $result = $helper->getReleaseNotes();

        self::assertStringContainsString("href='https://github.com/arendst/OpenBeken/issues/123'", $result);
        self::assertStringContainsString(
            'https://raw.githubusercontent.com/arendst/OpenBeken/master/tools/logo/OpenBeken_FullLogo_Vector.svg',
            $result
        );
    }

    public function testGetChangelogUsesStableChannelAndIssueLinks(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('get')
            ->with(self::stringContains('https://raw.githubusercontent.com/arendst/OpenBeken/master/CHANGELOG.md?r='))
            ->willReturn(new Response(200, [], 'See #456'))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $client,
            $this->createMock(OpenBekenOtaScraper::class),
            'stable'
        );

        $result = $helper->getChangelog();

        self::assertStringContainsString("href='https://github.com/arendst/OpenBeken/issues/456'", $result);
    }

    public function testGetChangelogUsesDevelopmentChannelUrl(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('get')
            ->with(self::stringContains('https://raw.githubusercontent.com/arendst/OpenBeken/development/CHANGELOG.md?r='))
            ->willReturn(new Response(200, [], 'See #789'))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $client,
            $this->createMock(OpenBekenOtaScraper::class),
            'dev'
        );

        $result = $helper->getChangelog();

        self::assertStringContainsString("href='https://github.com/arendst/OpenBeken/issues/789'", $result);
    }

    public function testGetReleaseNotesReturnsFailureMessageWhenRequestFails(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('get')
            ->with(self::stringContains('https://raw.githubusercontent.com/arendst/OpenBeken/development/RELEASENOTES.md?r='))
            ->willThrowException(new class('boom') extends \RuntimeException implements GuzzleException {})
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $client,
            $this->createMock(OpenBekenOtaScraper::class),
            'stable'
        );

        $result = $helper->getReleaseNotes();

        self::assertStringContainsString('Failed to load ', $result);
        self::assertStringContainsString('https://raw.githubusercontent.com/arendst/OpenBeken/development/RELEASENOTES.md?r=', $result);
        self::assertStringContainsString('boom', $result);
    }

    public function testGetReleasesReturnsSortedUniqueNamesAcrossArchitectures(): void
    {
        $scraper = $this->createMock(OpenBekenOtaScraper::class);
        $scraper->expects(self::once())
            ->method('getEsp8266Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken-display.bin.gz', 'https://ota/OpenBeken-display.bin.gz'),
                    new OpenBekenFirmware('OpenBeken.bin.gz', 'https://ota/OpenBeken.bin.gz'),
                    new OpenBekenFirmware('OpenBeken-display.bin.gz', 'https://ota/OpenBeken-display-dup.bin.gz'),
                    new OpenBekenFirmware('OpenBeken-minimal.bin.gz', 'https://ota/OpenBeken-minimal.bin.gz'),
                    new OpenBekenFirmware('notes.txt', 'https://ota/notes.txt'),
                ]
            ))
        ;
        $scraper->expects(self::once())
            ->method('getEsp32Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken32-zigbee.bin', 'https://ota/OpenBeken32-zigbee.bin'),
                    new OpenBekenFirmware('OpenBeken-display.bin', 'https://ota/OpenBeken-display.bin'),
                    new OpenBekenFirmware('OpenBeken32-zigbee.bin', 'https://ota/OpenBeken32-zigbee-dup.bin'),
                    new OpenBekenFirmware('readme.md', 'https://ota/readme.md'),
                ]
            ))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $this->createMock(Client::class),
            $scraper,
            'stable'
        );

        self::assertSame(
            ['OpenBeken', 'OpenBeken-display', 'OpenBeken32-zigbee'],
            $helper->getReleases()
        );
    }

    public function testGetLatestFirmwaresReturnsEsp8266FirmwareAndMinimalFirmware(): void
    {
        $scraper = $this->createMock(OpenBekenOtaScraper::class);
        $scraper->expects(self::once())
            ->method('getEsp8266Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken-minimal.bin.gz', 'https://ota/minimal.bin.gz'),
                    new OpenBekenFirmware('OpenBeken-sensors.bin.gz', 'https://ota/OpenBeken-sensors.bin.gz'),
                ]
            ))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $this->createMock(Client::class),
            $scraper,
            'stable'
        );

        $result = $helper->getLatestFirmwares('OpenBeken-sensors.bin');

        self::assertSame('https://ota/OpenBeken-sensors.bin.gz', $result->getFirmwareUrl());
        self::assertTrue($result->hasMinimalFirmware());
        self::assertSame('https://ota/minimal.bin.gz', $result->getMinimalFirmwareUrl());
        self::assertSame('v14.0.0', $result->getTagName());
    }

    public function testGetLatestFirmwaresReturnsEsp32FirmwareWithoutMinimalFirmware(): void
    {
        $scraper = $this->createMock(OpenBekenOtaScraper::class);
        $scraper->expects(self::once())
            ->method('getEsp32Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken32.bin', 'https://ota/OpenBeken32.bin'),
                ]
            ))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $this->createMock(Client::class),
            $scraper,
            'stable'
        );

        $result = $helper->getLatestFirmwares('OpenBeken32.bin');

        self::assertSame('https://ota/OpenBeken32.bin', $result->getFirmwareUrl());
        self::assertFalse($result->hasMinimalFirmware());
        self::assertNull($result->getMinimalFirmwareUrl());
    }

    public function testGetLatestFirmwaresResolvesNonDefaultEsp32Variant(): void
    {
        $scraper = $this->createMock(OpenBekenOtaScraper::class);
        $scraper->expects(self::once())
            ->method('getEsp32Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken32solo1.bin', 'https://ota/OpenBeken32solo1.bin'),
                ]
            ))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $this->createMock(Client::class),
            $scraper,
            'stable'
        );

        $result = $helper->getLatestFirmwares('OpenBeken32solo1.bin');

        self::assertSame('https://ota/OpenBeken32solo1.bin', $result->getFirmwareUrl());
        self::assertFalse($result->hasMinimalFirmware());
    }

    public function testGetLatestFirmwaresThrowsWhenConfiguredFirmwareCannotBeResolved(): void
    {
        $scraper = $this->createMock(OpenBekenOtaScraper::class);
        $scraper->expects(self::once())
            ->method('getEsp8266Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken-minimal.bin.gz', 'https://ota/minimal.bin.gz'),
                ]
            ))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $this->createMock(Client::class),
            $scraper,
            'stable'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to resolve firmware');

        $helper->getLatestFirmwares('OpenBeken-sensors.bin');
    }

    public function testGetLatestFirmwaresThrowsWhenMinimalFirmwareIsMissing(): void
    {
        $scraper = $this->createMock(OpenBekenOtaScraper::class);
        $scraper->expects(self::once())
            ->method('getEsp8266Firmware')
            ->willReturn(new OpenBekenFirmwareResult(
                'v14.0.0',
                new \DateTime('2026-06-11T00:00:00+00:00'),
                [
                    new OpenBekenFirmware('OpenBeken-sensors.bin.gz', 'https://ota/OpenBeken-sensors.bin.gz'),
                ]
            ))
        ;

        $helper = new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            $this->createMock(Client::class),
            $scraper,
            'stable'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to resolve firmware');

        $helper->getLatestFirmwares('OpenBeken-sensors.bin');
    }

    private function createHelper(): OpenBekenHelper
    {
        return new OpenBekenHelper(
            new GithubFlavoredMarkdownConverter(),
            new Client(),
            new OpenBekenOtaScraper('stable', new HttpBrowser(new OpenBekenHelperMockClient())),
            'stable'
        );
    }
}

class OpenBekenHelperMockClient extends MockHttpClient
{
    private string $baseUri = 'https://ota.OpenBeken.com';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);

        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url): MockResponse
    {
        if ('GET' === $method && 'https://ota.OpenBeken.com/OpenBeken/release/' === $url) {
            return $this->getFixtureResponse('stable.html');
        }

        if ('GET' === $method && 'https://ota.OpenBeken.com/OpenBeken32/release/' === $url) {
            return $this->getFixtureResponse('stable_esp32.html');
        }

        throw new \UnexpectedValueException("Mock not implemented: {$method} {$url}");
    }

    private function getFixtureResponse(string $fixture): MockResponse
    {
        return new MockResponse(
            TestUtils::loadFixture($fixture),
            ['http_code' => 200]
        );
    }
}
