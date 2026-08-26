<?php

namespace OpenBKAdmin\Helper;

use GuzzleHttp\Client;

/**
 * Reads firmware metadata exclusively from the official OpenBeken GitHub releases.
 * The automatic updater deliberately selects only the release asset documented as
 * "OTA Update" for a chipset/platform.
 */
class OpenBekenOtaScraper
{
    public const RELEASES_URL = 'https://github.com/openshwprojects/OpenBK7231T_App/releases';
    private const API_URL = 'https://api.github.com/repos/openshwprojects/OpenBK7231T_App/releases';

    private string $updateChannel;
    private Client $client;

    public function __construct(string $updateChannel, $client = null)
    {
        $this->updateChannel = $updateChannel;
        $this->client = $client instanceof Client ? $client : new Client([
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'OpenBKAdmin',
            ],
            'connect_timeout' => 10,
            'timeout' => 20,
        ]);
    }

    /** @return array<string,array{platform:string,filename:string,url:string,version:string,publishedAt:\DateTime}> */
    public function getOtaFirmwares(): array
    {
        $release = $this->getRelease();
        $body = (string) ($release['body'] ?? '');
        $assets = [];
        foreach (($release['assets'] ?? []) as $asset) {
            if (!empty($asset['name']) && !empty($asset['browser_download_url'])) {
                $assets[$asset['name']] = $asset['browser_download_url'];
            }
        }

        $result = [];
        foreach ($this->parseOtaTable($body) as $platform => $filename) {
            if (!isset($assets[$filename])) {
                continue;
            }
            $result[$platform] = [
                'platform' => $platform,
                'filename' => $filename,
                'url' => $assets[$filename],
                'version' => $this->extractVersion($filename, (string) ($release['tag_name'] ?? '')),
                'publishedAt' => new \DateTime((string) ($release['published_at'] ?? 'now')),
            ];
        }

        if (empty($result)) {
            throw new \RuntimeException('No OTA Update assets were found in the official OpenBeken GitHub release.');
        }

        ksort($result, SORT_NATURAL | SORT_FLAG_CASE);
        return $result;
    }

    public function getOtaFirmware(string $platform): array
    {
        $platform = strtoupper(trim($platform));
        $firmwares = $this->getOtaFirmwares();
        if (!isset($firmwares[$platform])) {
            throw new \InvalidArgumentException(sprintf('No OTA Update asset is published for chipset %s.', $platform));
        }
        return $firmwares[$platform];
    }

    private function getRelease(): array
    {
        $url = self::API_URL.('dev' === $this->updateChannel ? '?per_page=20' : '/latest');
        $data = json_decode((string) $this->client->get($url)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if ('dev' === $this->updateChannel) {
            foreach ($data as $release) {
                if (empty($release['draft'])) {
                    return $release;
                }
            }
            throw new \RuntimeException('No OpenBeken GitHub release is available.');
        }
        return $data;
    }

    /** @return array<string,string> */
    private function parseOtaTable(string $body): array
    {
        $rows = [];
        foreach (preg_split('/\R/', $body) as $line) {
            if (!str_contains($line, '|')) {
                continue;
            }
            $columns = array_map('trim', explode('|', trim($line, " \t|")));
            if (count($columns) < 3 || 0 !== strcasecmp($columns[1], 'OTA Update')) {
                continue;
            }
            $platform = strtoupper(preg_replace('/[`*_]/', '', $columns[0]));
            $filename = preg_replace('/[`*_]/', '', $columns[2]);
            if (preg_match('/\[([^\]]+)\]\([^\)]+\)/', $filename, $match)) {
                $filename = $match[1];
            }
            if ('' !== $platform && '' !== $filename) {
                $rows[$platform] = trim($filename);
            }
        }
        return $rows;
    }

    private function extractVersion(string $filename, string $tag): string
    {
        if (preg_match('/(?<!\d)(\d+\.\d+\.\d+(?:\.\d+)?)(?!\d)/', $filename, $match)) {
            return $match[1];
        }
        if (preg_match('/(?<!\d)(\d+\.\d+\.\d+(?:\.\d+)?)(?!\d)/', $tag, $match)) {
            return $match[1];
        }
        return ltrim($tag, 'vV');
    }
}
