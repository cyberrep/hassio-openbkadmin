<?php

namespace OpenBKAdmin\Helper;

use GuzzleHttp\Client;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use OpenBKAdmin\Update\AutoFirmwareResult;

class OpenBekenHelper
{
    public const RELEASES_URL = OpenBekenOtaScraper::RELEASES_URL;

    private OpenBekenOtaScraper $OpenBekenOtaScraper;

    public function __construct(
        GithubFlavoredMarkdownConverter $markDownParser,
        Client $client,
        OpenBekenOtaScraper $OpenBekenOtaScraper,
        string $channel
    ) {
        $this->OpenBekenOtaScraper = $OpenBekenOtaScraper;
    }

    public function getReleaseNotes(): string
    {
        return '<p>Firmware information is loaded from the official OpenBeken GitHub releases. Automatic updates use only assets marked <strong>OTA Update</strong> for each chipset.</p>';
    }

    public function getChangelog(): string
    {
        return '<p><a href="'.self::RELEASES_URL.'" target="_blank" rel="noopener">OpenBeken release notes</a></p>';
    }

    public function getReleases(): array
    {
        return $this->getOtaPlatforms();
    }

    public function getOtaPlatforms(): array
    {
        return array_keys($this->OpenBekenOtaScraper->getOtaFirmwares());
    }

    // Compatibility aliases for older UI code. They now expose OpenBeken chipsets,
    // not the legacy ESP8266/ESP32 Tasmota firmware families.
    public function getEsp8266Releases(): array
    {
        return $this->getOtaPlatforms();
    }

    public function getEsp32Releases(): array
    {
        return [];
    }

    public function getLatestFirmwares(string $configuredFirmware): AutoFirmwareResult
    {
        $firmware = $this->OpenBekenOtaScraper->getOtaFirmware($configuredFirmware);
        return new AutoFirmwareResult(
            $firmware['url'],
            null,
            $firmware['version'],
            $firmware['publishedAt']
        );
    }
}
