<?php

namespace OpenBKAdmin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use OpenBKAdmin\OpenBeken\ResponseParser;

class OpenBeken
{
    public const COMMAND_INFO_STATUS_ALL = 'status 0';
    private DeviceRepository $deviceRepository;

    private ResponseParser $responseParser;

    private Client $client;

    private Config $config;

    public function __construct(DeviceRepository $deviceRepository, Client $client, Config $config)
    {
        $this->deviceRepository = $deviceRepository;
        $this->responseParser = new ResponseParser();
        $this->client = $client;
        $this->config = $config;
    }

    public function getAllStatus(Device $device): \stdClass
    {
        return $this->doRequest($device, self::COMMAND_INFO_STATUS_ALL);
    }

    public function buildCmndUrl(Device $device, string $cmnd): string
    {
        return $this->buildUrl($device, 'cm', ['cmnd' => $cmnd]);
    }

    public function backup(Device $device, string $downloadPath): string
    {
        $url = $this->buildBasicAuthUrl($device, 'dl');
        $downloadFilePath = $downloadPath.$device->getBackupName();
        if (file_exists($downloadFilePath)) {
            unlink($downloadFilePath);
        }

        $this->client->get($url, ['sink' => $downloadFilePath]);

        return $downloadFilePath;
    }

    public function restore(Device $device, string $backupUrl): \stdClass
    {
        return $this->doRequest($device, sprintf('WebGetConfig %s', $backupUrl));
    }

    public function getNTPStatus(Device $device)
    {
        $cmnd = 'NtpServer1';

        $status = $this->doRequest($device, $cmnd);
        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }

        return $status;
    }

    public function getFullTopic(Device $device): string
    {
        $cmnd = 'FullTopic';

        $status = $this->doRequest($device, $cmnd);
        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }

        if (!empty($status->ERROR)) {
            return '';
        }

        if (!empty($status->WARNING)) {
            return '';
        }

        return $status->FullTopic;
    }

    public function getSwitchTopic(Device $device): string
    {
        $cmnd = 'SwitchTopic';

        $status = $this->doRequest($device, $cmnd);

        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }

        if (!empty($status->ERROR)) {
            return '';
        }

        if (!empty($status->WARNING)) {
            return '';
        }

        return $status->SwitchTopic;
    }

    public function getMqttRetry(Device $device): string
    {
        $cmnd = 'MqttRetry';

        $status = $this->doRequest($device, $cmnd);
        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }

        if (!empty($status->ERROR)) {
            return '';
        }

        if (!empty($status->WARNING)) {
            return '';
        }

        return $status->MqttRetry;
    }

    public function getTelePeriod(Device $device): string
    {
        $cmnd = 'TelePeriod';

        $status = $this->doRequest($device, $cmnd);
        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }

        if (!empty($status->ERROR)) {
            return '';
        }

        if (!empty($status->WARNING)) {
            return '';
        }

        return $status->TelePeriod;
    }

    public function getSensorRetain(Device $device): string
    {
        $cmnd = 'SensorRetain';

        $status = $this->doRequest($device, $cmnd);
        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }

        if (!empty($status->ERROR)) {
            return '';
        }

        if (!empty($status->WARNING)) {
            return '';
        }

        return $status->SensorRetain;
    }

    public function getMqttFingerprint(Device $device): string
    {
        $cmnd = 'MqttFingerprint';

        $status = $this->doRequest($device, $cmnd);
        if (!empty($status->Command) && 'Unknown' === $status->Command) {
            return '';
        }
        if (!empty($status->ERROR)) {
            return '';
        }

        if (!empty($status->WARNING)) {
            return '';
        }

        if (empty($status->MqttFingerprint)) {
            return '';
        }

        return $status->MqttFingerprint;
    }

    public function getPrefixe(Device $device): \stdClass
    {
        $cmnds = ['Prefix1', 'Prefix2', 'Prefix3'];

        $status = new \stdClass();
        foreach ($cmnds as $cmnd) {
            $tmp = $this->doRequest($device, $cmnd);

            if (!empty($tmp->Command) && 'Unknown' === $tmp->Command) {
                $status->{$cmnd} = '';
            } elseif (!empty($tmp->ERROR)) {
                $status->{$cmnd} = '';
            } else {
                $status->{$cmnd} = $tmp->{$cmnd};
            }
        }

        unset($tmp);

        return $status;
    }

    public function getStateTexts(Device $device): \stdClass
    {
        $cmnds = ['StateText1', 'StateText2', 'StateText3', 'StateText4'];

        $status = new \stdClass();
        foreach ($cmnds as $cmnd) {
            $tmp = $this->doRequest($device, $cmnd);
            if (!empty($tmp->Command) && 'Unknown' === $tmp->Command) {
                $status->{$cmnd} = '';
            } elseif (!empty($tmp->ERROR)) {
                $status->{$cmnd} = '';
            } else {
                $status->{$cmnd} = $tmp->{$cmnd};
            }
        }

        unset($tmp);

        return $status;
    }

    public function getTimersConfig(Device $device): ?\stdClass
    {
        $timersStatus = $this->doRequest($device, 'Timers');
        if ($this->isUnsupportedOrInvalidResponse($timersStatus)) {
            return null;
        }

        $timersConfig = new \stdClass();
        $timersConfig->enabled = $this->normalizeTimerToggle($timersStatus->Timers ?? null);
        $timersConfig->timers = [];

        foreach (range(1, 16) as $timerIndex) {
            $timerStatus = $this->doRequest($device, 'Timer'.$timerIndex);
            $timerConfig = null;
            $timerKey = 'Timer'.$timerIndex;

            if (!$this->isUnsupportedOrInvalidResponse($timerStatus) && isset($timerStatus->{$timerKey})) {
                $timerConfig = $this->normalizeTimerDefinition($timerStatus->{$timerKey});
            }

            $timersConfig->timers[$timerIndex] = $timerConfig ?? $this->getDefaultTimerDefinition();
        }

        return $timersConfig;
    }

    public function saveConfig(Device $device, string $backlog): \stdClass
    {
        return $this->doRequest($device, $backlog);
    }

    public function saveTimers(Device $device, array $settings): \stdClass
    {
        $result = $this->doRequest($device, 'Timers '.($settings['Timers'] ?? '0'));

        foreach (range(1, 16) as $timerIndex) {
            $timerKey = 'Timer'.$timerIndex;
            if (!isset($settings[$timerKey]) || !is_array($settings[$timerKey])) {
                continue;
            }

            $timerSettings = $this->normalizeTimerSettingsForSave($settings[$timerKey]);
            $result = $this->doRequest(
                $device,
                $timerKey.' '.json_encode($timerSettings, JSON_UNESCAPED_SLASHES)
            );
        }

        return $result;
    }

    public function doAjax($deviceId, string $cmnd)
    {
        $device = $this->getDeviceById($deviceId);

        if (null === $device) {
            $response = new \stdClass();
            $response->ERROR = sprintf('No devices found with ID: %d', $deviceId);

            return $response;
        }

        $url = $this->buildCmndUrl($device, $cmnd);

        try {
            $response = $this->client->request('GET', $url, $this->getHttpOptions($device));

            return $this->responseParser->processResult($response->getBody()->getContents());
        } catch (GuzzleException $exception) {
            $result = new \stdClass();
            $result->ERROR = $exception->getMessage();

            return $result;
        }
    }

    public function getDeviceById($id = null): ?Device
    {
        return $this->deviceRepository->getDeviceById($id);
    }

    public function doAjaxAll(): array
    {
        ini_set('max_execution_time', Constants::EXTENDED_MAX_EXECUTION_TIME);

        // A multi-channel OpenBeken device is stored as several logical rows.
        // Query the physical device only once and reuse its status for all rows
        // that share IP:port. This avoids 4 HTTP requests for a 4-gang relay.
        $results = [];
        $statusByAddress = [];

        foreach ($this->getDevices() as $device) {
            $addressKey = $device->ip.':'.$device->port;

            if (!array_key_exists($addressKey, $statusByAddress)) {
                $statusByAddress[$addressKey] = $this->getOpenBekenStatus($device);
            }

            $results[$device->id] = $statusByAddress[$addressKey];
        }

        ini_set('max_execution_time', Constants::DEFAULT_MAX_EXECUTION_TIME);

        return $results;
    }

    public function setDeviceValue(int $id, string $field, $value = null): ?Device
    {
        return $this->deviceRepository->setDeviceValue($id, $field, $value);
    }

    /**
     * @return Device[]
     */
    public function getDevices(): array
    {
        $repositoryDevices = $this->deviceRepository->getDevices();

        $devices = [];
        $update = false;
        foreach ($repositoryDevices as $device) {
            if (0 === $device->position) {
                $device->position = 1;
                $update = true;
            }
            while (isset($devices[$device->position])) {
                ++$device->position;
            }
            if ($update) {
                $this->deviceRepository->setDeviceValue($device->id, 'position', $device->position);
            }
            $devices[$device->position] = $device;
        }
        ksort($devices);

        return $devices;
    }

    public function search($urls = []): array
    {
        ini_set('max_execution_time', Constants::EXTENDED_MAX_EXECUTION_TIME);

        $requests = function ($urls) {
            foreach ($urls as $url) {
                yield new Request('GET', $url);
            }
        };

        $results = [];
        $pool = new Pool($this->client, $requests($urls), [
            'concurrency' => $this->config->getRequestConcurrency(),
            'fulfilled' => function (Response $response, $index) use (&$results) {
                $results[] = $this->responseParser->processResult($response->getBody()->getContents());
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        ini_set('max_execution_time', Constants::DEFAULT_MAX_EXECUTION_TIME);

        return $results;
    }

    public function decodeOptions($options)
    {
        if (empty($options)) {
            return false;
        }
        $a_setoption = [
            // OpenBeken\tools\decode-status.py
            'Save power state and use after restart',
            'Restrict button actions to single, double and hold',
            'Show value units in JSON messages',
            'MQTT enabled',
            'Respond as Command topic instead of RESULT',
            'MQTT retain on Power',
            'MQTT retain on Button',
            'MQTT retain on Switch',
            'Convert temperature to Fahrenheit',
            'MQTT retain on Sensor',
            'MQTT retained LWT to OFFLINE when topic changes',
            'Swap Single and Double press Button',
            'Do not use flash page rotate',
            'Button single press only',
            'Power interlock mode',
            'Do not allow PWM control',
            'Reverse clock',
            'Allow entry of decimal color values',
            'CO2 color to light signal',
            'HASS discovery',
            'Do not control Power with Dimmer',
            'Energy monitoring while powered off',
            'MQTT serial',
            'Rules',
            'Rules once mode',
            'KNX',
            'Use Power device index on single relay devices',
            'KNX enhancement',
            '',
            '',
            '',
            '',
        ];

        if (is_array($options)) {
            $options = $options[0];
        }

        $decodedOptopns = new \stdClass();

        $options = intval($options, 16);
        foreach ($a_setoption as $i => $iValue) {
            $optionV = ($options >> $i) & 1;
            $SetOPtion = 'SetOption'.$i;
            $decodedOptopns->{$SetOPtion} = new \stdClass();
            $decodedOptopns->{$SetOPtion}->desc = $iValue;
            $decodedOptopns->{$SetOPtion}->value = $optionV;
        }

        return $decodedOptopns;
    }

    private function getDefaultTimerDefinition(): \stdClass
    {
        return (object) [
            'Enable' => 0,
            'Mode' => 0,
            'Time' => '00:00',
            'Window' => 0,
            'Days' => '-------',
            'Repeat' => 0,
            'Output' => 1,
            'Action' => 0,
        ];
    }

    private function isUnsupportedOrInvalidResponse(\stdClass $result): bool
    {
        if (!empty($result->ERROR) || !empty($result->WARNING)) {
            return true;
        }

        return !empty($result->Command) && 'Unknown' === $result->Command;
    }

    /**
     * @param mixed $timerDefinition
     */
    private function normalizeTimerDefinition($timerDefinition): \stdClass
    {
        if (is_string($timerDefinition)) {
            $decoded = json_decode($timerDefinition);
            if ($decoded instanceof \stdClass) {
                $timerDefinition = $decoded;
            }
        } elseif (is_array($timerDefinition)) {
            $timerDefinition = (object) $timerDefinition;
        }

        $defaultTimer = $this->getDefaultTimerDefinition();
        if (!$timerDefinition instanceof \stdClass) {
            return $defaultTimer;
        }

        foreach (array_keys(get_object_vars($defaultTimer)) as $property) {
            if (!property_exists($timerDefinition, $property)) {
                $timerDefinition->{$property} = $defaultTimer->{$property};
            }
        }

        $timerDefinition->Enable = (int) $timerDefinition->Enable;
        $timerDefinition->Mode = (int) $timerDefinition->Mode;
        $timerDefinition->Time = trim((string) $timerDefinition->Time);
        $timerDefinition->Window = (int) $timerDefinition->Window;
        $timerDefinition->Days = trim((string) $timerDefinition->Days);
        $timerDefinition->Repeat = (int) $timerDefinition->Repeat;
        $timerDefinition->Output = (int) $timerDefinition->Output;
        $timerDefinition->Action = (int) $timerDefinition->Action;

        return $timerDefinition;
    }

    private function normalizeTimerToggle($value): int
    {
        if (is_string($value)) {
            return 'ON' === strtoupper($value) ? 1 : 0;
        }

        return (int) ((bool) $value);
    }

    private function normalizeTimerSettingsForSave(array $settings): array
    {
        return [
            'Enable' => (int) ($settings['Enable'] ?? 0),
            'Mode' => (int) ($settings['Mode'] ?? 0),
            'Time' => trim((string) ($settings['Time'] ?? '00:00')),
            'Window' => (int) ($settings['Window'] ?? 0),
            'Days' => trim((string) ($settings['Days'] ?? '-------')),
            'Repeat' => (int) ($settings['Repeat'] ?? 0),
            'Output' => (int) ($settings['Output'] ?? 1),
            'Action' => (int) ($settings['Action'] ?? 0),
        ];
    }

    /**
     * OpenBeken web security uses HTTP authentication.  The original
     * OpenBKAdmin client puts user/password in the query string, which causes
     * protected OpenBeken devices to return HTTP 401.
     *
     * CURLAUTH_ANY lets libcurl honor the device's WWW-Authenticate challenge
     * (Basic/Digest) without us guessing which mode a firmware build uses.
     */
    private function getHttpOptions(Device $device): array
    {
        if (empty($device->password)) {
            return [];
        }

        return [
            RequestOptions::CURL => [
                CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                CURLOPT_USERPWD => $device->username.':'.$device->password,
            ],
        ];
    }

    /**
     * Probe a command without passing credentials in the URL.
     */
    private function probeCommand(Device $device, string $cmnd): \stdClass
    {
        $url = $this->buildCmndUrl($device, $cmnd);

        try {
            $result = $this->client->get($url, $this->getHttpOptions($device))->getBody();
        } catch (GuzzleException $exception) {
            $data = new \stdClass();
            $data->ERROR = __('CURL_ERROR', 'API').' => '.$exception->getMessage();

            return $data;
        }

        return $this->responseParser->processResult($result->getContents());
    }

    /**
     * Detect OpenBeken using commands that belong to OpenBeken itself instead
     * of the OpenBeken-compatible "status 0" command.
     *
     * obkDeviceList is preferred because it is explicitly an OBK command and
     * does not change configuration. ShortName is used as a second signature.
     */
    public function isOpenBeken(Device $device): bool
    {
        foreach (['obkDeviceList', 'ShortName'] as $command) {
            $data = $this->probeCommand($device, $command);

            if (!empty($data->ERROR)) {
                continue;
            }

            if (!empty($data->Command) && 'unknown' === strtolower((string) $data->Command)) {
                continue;
            }

            // A successful response to either OBK-specific command is enough.
            return true;
        }

        return false;
    }

    private function extractOpenBekenCommandValue($response, array $keys): string
    {
        if (!is_object($response)) {
            return '';
        }

        foreach ($keys as $key) {
            if (isset($response->{$key}) && is_scalar($response->{$key})) {
                $value = trim((string) $response->{$key});
                if ('' !== $value) {
                    return $value;
                }
            }
        }

        // OpenBeken builds may return command output in generic fields.
        foreach (['Value', 'value', 'Result', 'result', 'Command', 'command', 'Message', 'message'] as $key) {
            if (!isset($response->{$key}) || !is_scalar($response->{$key})) {
                continue;
            }
            $value = trim((string) $response->{$key});
            foreach ($keys as $wanted) {
                if (preg_match('/(?:^|[\s:=])'.preg_quote($wanted, '/').'[\s:=]+(.+)$/i', $value, $m)) {
                    return trim($m[1], " \t\n\r\0\x0B\"");
                }
            }
            // If this is just a plain returned value, accept it.
            if ('' !== $value && false === stripos($value, 'ERROR')) {
                return trim($value, " \t\n\r\0\x0B\"");
            }
        }

        return '';
    }

    public function getOpenBekenInfo(Device $device): \stdClass
    {
        $info = new \stdClass();
        $info->ShortName = '';
        $info->FriendlyName = '';
        $info->ChannelLabels = [];

        $short = $this->probeCommand($device, 'ShortName');
        $info->ShortName = $this->extractOpenBekenCommandValue($short, ['ShortName', 'shortName']);

        $friendly = $this->probeCommand($device, 'FriendlyName');
        $info->FriendlyName = $this->extractOpenBekenCommandValue($friendly, ['FriendlyName', 'friendlyName', 'Name']);

        // Best-effort extraction of SetChannelLabel from LittleFS/autoexec.
        // Different OBK builds expose LFS files through different paths.
        foreach (['/api/lfs/autoexec.bat', '/lfs/autoexec.bat', '/autoexec.bat'] as $path) {
            try {
                $url = sprintf('http://%s:%s%s', $device->ip, $device->port, $path);
                $response = $this->client->get($url, $this->getHttpOptions($device));
                $body = (string) $response->getBody();
                if ('' === trim($body) || false !== stripos($body, '<html')) {
                    continue;
                }
                if (preg_match_all('/SetChannelLabel\s+(\d+)\s+(?:"([^"]+)"|([^\r\n;]+))/i', $body, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $label = trim($match[2] !== '' ? $match[2] : $match[3]);
                        if ('' !== $label) {
                            $info->ChannelLabels[(int) $match[1]] = $label;
                        }
                    }
                    if ([] !== $info->ChannelLabels) {
                        ksort($info->ChannelLabels);
                        break;
                    }
                }
            } catch (GuzzleException $exception) {
                // Optional source: ignore and try next path.
            }
        }

        return $info;
    }

    public function getOpenBekenStatus(Device $device): \stdClass
    {
        $status = $this->getAllStatus($device);
        if (isset($status->ERROR)) {
            return $status;
        }

        $info = $this->getOpenBekenInfo($device);
        $status->OpenBeken = $info;

        if (!isset($status->Status)) {
            $status->Status = new \stdClass();
        }
        if ('' !== $info->FriendlyName) {
            $status->Status->DeviceName = $info->FriendlyName;
        }
        if ('' !== $info->ShortName) {
            $status->Status->Topic = $info->ShortName;
        }

        // Prefer actual OBK channel labels when available.
        if ([] !== $info->ChannelLabels) {
            $status->Status->FriendlyName = array_values($info->ChannelLabels);
        }

        return $status;
    }

    private function doRequest(Device $device, string $cmnd, int $try = 1): \stdClass
    {
        $url = $this->buildCmndUrl($device, $cmnd);

        try {
            $result = $this->client->get($url, $this->getHttpOptions($device))->getBody();
        } catch (GuzzleException $exception) {
            $result = new \stdClass();
            $result->ERROR = __('CURL_ERROR', 'API').' => '.$exception->getMessage();

            return $result;
        }

        $data = $this->responseParser->processResult($result->getContents());

        $skipWarning = false;
        if (str_contains($cmnd, 'Backlog')) {
            $skipWarning = true;
        }

        if (!$skipWarning && !empty($data->WARNING) && 1 === $try) {
            ++$try;
            // set web log level 2 and try again
            $webLog = $this->setWebLog($device, 2, $try);
            if (!isset($webLog->WARNING)) {
                $data = $this->doRequest($device, $cmnd, $try);
            }
        }

        return $data;
    }

    private function buildUrl(Device $device, string $endpoint, array $args = []): string
    {
        // OpenBeken credentials are sent through HTTP authentication by
        // getHttpOptions(); never expose them in the query string.
        $queryParams = $args;
        $queryString = '?'.http_build_query($queryParams);

        return sprintf('http://%s:%s/%s%s', $device->ip, $device->port, $endpoint, $queryString);
    }

    private function buildBasicAuthUrl(Device $device, string $endpoint): string
    {
        $basicAuth = '';
        if (!empty($device->password)) {
            $basicAuth = rawurlencode($device->username).':'.rawurlencode($device->password).'@';
        }

        return sprintf('http://%s%s/%s', $basicAuth, $device->ip, $endpoint);
    }

    private function setWebLog(Device $device, int $level = 2, int $try = 1): \stdClass
    {
        $cmnd = 'Weblog '.$level;

        return $this->doRequest($device, $cmnd, $try);
    }

    public function getOpenBekenNativeNames(Device $device): \stdClass
    {
        $r = new \stdClass(); $r->FullName = ''; $r->ShortName = '';
        try {
            $url = sprintf('http://%s:%s/cfg_name', $device->ip, $device->port);
            $response = $this->client->get($url, $this->getHttpOptions($device));
            $html = (string) $response->getBody();
            $dec = static fn (string $v): string => trim(html_entity_decode(strip_tags($v), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            foreach (['friendlyName','fullName','devName','deviceName'] as $field) {
                $f=preg_quote($field,'/');
                if (preg_match('/<input[^>]*name=["\']'.$f.'["\'][^>]*value=["\']([^"\']*)["\']/i',$html,$m)
                    || preg_match('/<input[^>]*value=["\']([^"\']*)["\'][^>]*name=["\']'.$f.'["\']/i',$html,$m)) {
                    $r->FullName=$dec($m[1]); if(''!==$r->FullName)break;
                }
            }
            if (''===$r->FullName && preg_match('/Full\s*Name\s*:?.{0,700}?<input[^>]*value=["\']([^"\']+)["\']/is',$html,$m))
                $r->FullName=$dec($m[1]);
            foreach (['shortName','shortname'] as $field) {
                $f=preg_quote($field,'/');
                if (preg_match('/<input[^>]*name=["\']'.$f.'["\'][^>]*value=["\']([^"\']*)["\']/i',$html,$m)
                    || preg_match('/<input[^>]*value=["\']([^"\']*)["\'][^>]*name=["\']'.$f.'["\']/i',$html,$m)) {
                    $r->ShortName=$dec($m[1]);break;
                }
            }
        } catch (\Throwable $e) {}
        return $r;
    }

}
