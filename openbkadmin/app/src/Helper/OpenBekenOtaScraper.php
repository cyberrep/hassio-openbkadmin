<?php

namespace OpenBKAdmin\Helper;

use GuzzleHttp\Client;

/**
 * Reads firmware metadata exclusively from the official OpenBeken GitHub releases.
 * Only the normal OTA Update image for each chipset is exposed.
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
        $tag = (string) ($release['tag_name'] ?? '');
        $publishedAt = new \DateTime((string) ($release['published_at'] ?? 'now'));
        $assets = [];

        foreach (($release['assets'] ?? []) as $asset) {
            $name = (string) ($asset['name'] ?? '');
            $url = (string) ($asset['browser_download_url'] ?? '');
            if ($name !== '' && $url !== '') {
                $assets[$name] = $url;
            }
        }

        // The release body is the authoritative mapping of Platform -> Usage -> Filename.
        // Match the Usage column as OTA Update and then resolve that filename against assets.
        $result = [];
        foreach ($this->parseOtaTable((string) ($release['body'] ?? '')) as $platform => $filename) {
            $url = $assets[$filename] ?? null;
            if ($url === null) {
                // GitHub's generated release body may contain a linked filename or minor formatting
                // differences. Fall back to an exact case-insensitive asset-name match.
                foreach ($assets as $assetName => $assetUrl) {
                    if (strcasecmp($assetName, $filename) === 0) {
                        $filename = $assetName;
                        $url = $assetUrl;
                        break;
                    }
                }
            }
            if ($url === null) {
                continue;
            }
            $result[$platform] = [
                'platform' => $platform,
                'filename' => $filename,
                'url' => $url,
                'version' => $this->extractVersion($filename, $tag),
                'publishedAt' => $publishedAt,
            ];
        }

        // Defensive fallback: infer only canonical OTA filenames from release assets.
        // This keeps updates working if GitHub changes markdown rendering, while avoiding
        // variant builds such as battery/powerMetering/berry unless the release table says so.
        if (empty($result)) {
            foreach ($assets as $filename => $url) {
                if (!preg_match('/^Open([A-Za-z0-9]+)_(\d+\.\d+\.\d+)(?:\.rbl|_ota\.img|_OTA\.bin(?:\.xz\.ota)?|_gz\.img)$/i', $filename, $m)) {
                    continue;
                }
                $platform = strtoupper($m[1]);
                $result[$platform] = [
                    'platform' => $platform,
                    'filename' => $filename,
                    'url' => $url,
                    'version' => $m[2],
                    'publishedAt' => $publishedAt,
                ];
            }
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
            if (count($columns) < 3) {
                continue;
            }
            $usage = trim(preg_replace('/[`*_]/', '', $columns[1]));
            if (strcasecmp($usage, 'OTA Update') !== 0) {
                continue;
            }
            $platform = strtoupper(trim(preg_replace('/[`*_]/', '', $columns[0])));
            $filenameCell = trim($columns[2]);
            if (preg_match('/\[([^\]]+)\]\([^\)]+\)/', $filenameCell, $match)) {
                $filenameCell = $match[1];
            }
            $filename = trim(preg_replace('/[`*_]/', '', $filenameCell));
            if ($platform !== '' && $filename !== '') {
                $rows[$platform] = $filename;
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
