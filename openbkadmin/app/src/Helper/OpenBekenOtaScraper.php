<?php

namespace OpenBKAdmin\Helper;

use GuzzleHttp\Client;

class OpenBekenOtaScraper
{
    public const RELEASES_URL = 'https://github.com/openshwprojects/OpenBK7231T_App/releases';
    private const API_URL = 'https://api.github.com/repos/openshwprojects/OpenBK7231T_App/releases';
    private const CACHE_TTL = 900;

    private string $updateChannel;
    private Client $client;
    private ?array $releaseCache = null;

    public function __construct(string $updateChannel, $client = null)
    {
        $this->updateChannel = $updateChannel;
        $this->client = $client instanceof Client ? $client : new Client([
            'headers' => ['Accept' => 'application/vnd.github+json', 'User-Agent' => 'OpenBKAdmin'],
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
            if ($name !== '' && $url !== '') $assets[$name] = $url;
        }

        $result = [];
        foreach ($this->parseOtaTable((string) ($release['body'] ?? '')) as $platform => $filename) {
            $url = $assets[$filename] ?? null;
            if ($url === null) {
                foreach ($assets as $assetName => $assetUrl) {
                    if (strcasecmp($assetName, $filename) === 0) { $filename = $assetName; $url = $assetUrl; break; }
                }
            }
            if ($url === null) continue;
            $result[$platform] = ['platform'=>$platform,'filename'=>$filename,'url'=>$url,'version'=>$this->extractVersion($filename,$tag),'publishedAt'=>$publishedAt];
        }

        if (empty($result)) {
            foreach ($assets as $filename => $url) {
                if (!preg_match('/^Open([A-Za-z0-9]+)_(\d+\.\d+\.\d+)(?:\.rbl|_ota\.img|_OTA\.bin(?:\.xz\.ota)?|_gz\.img)$/i', $filename, $m)) continue;
                $platform = strtoupper($m[1]);
                $result[$platform] = ['platform'=>$platform,'filename'=>$filename,'url'=>$url,'version'=>$m[2],'publishedAt'=>$publishedAt];
            }
        }
        ksort($result, SORT_NATURAL | SORT_FLAG_CASE);
        return $result;
    }

    public function getOtaFirmware(string $platform): array
    {
        $platform = strtoupper(trim($platform));
        $firmwares = $this->getOtaFirmwares();
        if (!isset($firmwares[$platform])) throw new \InvalidArgumentException(sprintf('No OTA Update asset is published for chipset %s.', $platform));
        return $firmwares[$platform];
    }

    private function getRelease(): array
    {
        if ($this->releaseCache !== null) return $this->releaseCache;
        $cacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'openbkadmin-openbeken-release-'.($this->updateChannel === 'dev' ? 'dev' : 'stable').'.json';
        $cached = null;
        if (is_file($cacheFile)) {
            $decoded = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($decoded) && is_array($decoded['release'] ?? null)) {
                $cached = $decoded;
                if ((time() - (int) ($decoded['saved_at'] ?? 0)) < self::CACHE_TTL) return $this->releaseCache = $decoded['release'];
            }
        }

        try {
            $url = self::API_URL.('dev' === $this->updateChannel ? '?per_page=20' : '/latest');
            $data = json_decode((string) $this->client->get($url)->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if ('dev' === $this->updateChannel) {
                $release = null;
                foreach ($data as $candidate) if (empty($candidate['draft'])) { $release = $candidate; break; }
                if (!is_array($release)) throw new \RuntimeException('No OpenBeken GitHub release is available.');
            } else {
                $release = $data;
            }
        } catch (\Throwable $apiError) {
            // GitHub's REST API has a low unauthenticated hourly limit. The public
            // release page is not subject to that API quota, so use it as fallback.
            try {
                $release = $this->getReleaseFromHtml();
            } catch (\Throwable $htmlError) {
                if (is_array($cached['release'] ?? null)) return $this->releaseCache = $cached['release'];
                throw new \RuntimeException('Unable to read the official OpenBeken release metadata. '.$apiError->getMessage(), 0, $apiError);
            }
        }

        @file_put_contents($cacheFile, json_encode(['saved_at'=>time(),'release'=>$release]));
        return $this->releaseCache = $release;
    }

    private function getReleaseFromHtml(): array
    {
        $html = (string) $this->client->get(self::RELEASES_URL.'/latest', [
            'headers' => ['Accept' => 'text/html', 'User-Agent' => 'OpenBKAdmin'],
        ])->getBody();
        $assets = [];
        $tag = '';
        if (preg_match_all('#href=["\']([^"\']*/openshwprojects/OpenBK7231T_App/releases/download/([^/]+)/([^"\'?]+))#i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (str_starts_with($href, '/')) $href = 'https://github.com'.$href;
                $tag = $tag !== '' ? $tag : rawurldecode($match[2]);
                $name = rawurldecode($match[3]);
                $assets[$name] = ['name'=>$name,'browser_download_url'=>$href];
            }
        }
        if (empty($assets)) throw new \RuntimeException('No release assets found on the OpenBeken release page.');
        return ['tag_name'=>$tag,'published_at'=>date(DATE_ATOM),'body'=>'','assets'=>array_values($assets)];
    }

    /** @return array<string,string> */
    private function parseOtaTable(string $body): array
    {
        $rows = [];
        foreach (preg_split('/\R/', $body) as $line) {
            if (!str_contains($line, '|')) continue;
            $columns = array_map('trim', explode('|', trim($line, " \t|")));
            if (count($columns) < 3) continue;
            $usage = trim(preg_replace('/[`*_]/', '', $columns[1]));
            if (strcasecmp($usage, 'OTA Update') !== 0) continue;
            $platform = strtoupper(trim(preg_replace('/[`*_]/', '', $columns[0])));
            $filenameCell = trim($columns[2]);
            if (preg_match('/\[([^\]]+)\]\([^\)]+\)/', $filenameCell, $match)) $filenameCell = $match[1];
            $filename = trim(preg_replace('/[`*_]/', '', $filenameCell));
            if ($platform !== '' && $filename !== '') $rows[$platform] = $filename;
        }
        return $rows;
    }

    private function extractVersion(string $filename, string $tag): string
    {
        if (preg_match('/(?<!\d)(\d+\.\d+\.\d+(?:\.\d+)?)(?!\d)/', $filename, $match)) return $match[1];
        if (preg_match('/(?<!\d)(\d+\.\d+\.\d+(?:\.\d+)?)(?!\d)/', $tag, $match)) return $match[1];
        return ltrim($tag, 'vV');
    }
}
