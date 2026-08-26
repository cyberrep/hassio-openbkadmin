<?php

namespace OpenBKAdmin\Mqtt;

use OpenBKAdmin\Device;
use OpenBKAdmin\DeviceRepository;
use OpenBKAdmin\OpenBeken\ResponseParser;

class MqttDiscoveryService
{
    public function __construct(private DeviceRepository $deviceRepository, private ResponseParser $responseParser, private MqttClientFactoryInterface $mqttClientFactory, private TimeProviderInterface $timeProvider) {}

    public function scan(MqttDiscoveryRequest $request): MqttDiscoveryResult
    {
        $client = $this->mqttClientFactory->create($request->host, $request->port);
        $discoveredTopics = []; $publishedTopics = []; $statusPayloads = []; $nativeIps = [];
        $loopStartedAt = $this->timeProvider->now();

        $handleMessage = function (string $topic, string $message) use (&$discoveredTopics, &$publishedTopics, &$statusPayloads, &$nativeIps, $request, $client): void {
            // Tasmota-compatible STATUS0 remains supported.
            $statusTopic = $this->extractStatusTopic($topic, $request->statPrefix);
            if (null !== $statusTopic) { $statusPayloads[$statusTopic] = $message; $discoveredTopics[$statusTopic] = true; return; }

            // Native OpenBeken MQTT is the preferred discovery path. OpenBeken
            // publishes <base-topic>/connected and <base-topic>/ip. The base topic
            // may itself contain slashes, e.g. openbeken/luz_cozinha.
            $nativeBase = $this->extractNativeBaseTopic($topic, 'connected');
            if (null !== $nativeBase) {
                $discoveredTopics[$nativeBase] = true;
                // Ask the device to publish its IP immediately instead of waiting
                // for the periodic MQTT broadcast interval.
                if (!isset($publishedTopics['native-ip:'.$nativeBase])) {
                    $client->publish($nativeBase.'/ip/get', '');
                    $publishedTopics['native-ip:'.$nativeBase] = true;
                }
                return;
            }

            $nativeBase = $this->extractNativeBaseTopic($topic, 'ip');
            if (null === $nativeBase) $nativeBase = $this->extractNativeBaseTopic($topic, 'ip/get');
            if (null !== $nativeBase && filter_var(trim($message), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $discoveredTopics[$nativeBase] = true;
                $nativeIps[$nativeBase] = trim($message);
                return;
            }

            // Legacy Tasmota TELE/LWT discovery for installations that enable
            // OpenBeken's Tasmota TELE compatibility flag.
            $mqttTopic = $this->extractDiscoveryTopic($topic, $request->telePrefix);
            if (null === $mqttTopic) return;
            $discoveredTopics[$mqttTopic] = true;
            if (isset($publishedTopics['status:'.$mqttTopic])) return;
            $client->publish($this->buildTopic($request->commandPrefix, $mqttTopic, 'STATUS'), '0');
            $publishedTopics['status:'.$mqttTopic] = true;
        };

        try {
            $client->connect($request->username, $request->password, $request->timeoutSeconds);
            foreach ($request->subscriptionFilters as $subscriptionFilter) $client->subscribe($subscriptionFilter, $handleMessage);
            // STATUS responses may be outside the discovery filter.
            $client->subscribe($this->buildPrefixWildcardTopic($request->statPrefix), $handleMessage);
            $deadline = $this->timeProvider->now() + max($request->timeoutSeconds, 1);
            while ($this->timeProvider->now() < $deadline) { $client->loopOnce($loopStartedAt); $this->timeProvider->sleep(50_000); }
        } finally { $client->disconnect(); }

        return $this->buildResult(array_keys($discoveredTopics), $statusPayloads, $nativeIps, $request);
    }

    private function buildResult(array $discoveredTopics, array $statusPayloads, array $nativeIps, MqttDiscoveryRequest $request): MqttDiscoveryResult
    {
        $result = new MqttDiscoveryResult(); $claimedAddresses = [];
        foreach ($discoveredTopics as $mqttTopic) {
            if ($this->deviceRepository->isMqttTopicAmbiguous($mqttTopic)) { $result->conflicts[]=['mqttTopic'=>$mqttTopic,'reason'=>'existing-topic-duplicate']; continue; }

            if (array_key_exists($mqttTopic, $statusPayloads)) {
                $status = $this->responseParser->processResult($statusPayloads[$mqttTopic]);
            } elseif (array_key_exists($mqttTopic, $nativeIps)) {
                // Minimal status is enough for MQTT discovery. The normal device
                // page will retrieve full OpenBeken status over HTTP afterwards.
                $status = new \stdClass(); $status->StatusNET = new \stdClass();
                $status->StatusNET->IPAddress = $nativeIps[$mqttTopic];
                $status->Status = new \stdClass(); $status->Status->FriendlyName = [];
                $status->OpenBeken = new \stdClass(); $status->OpenBeken->FullName = $mqttTopic;
            } else { $result->offlineTopics[]=['mqttTopic'=>$mqttTopic,'reason'=>'missing-ip-or-status-response']; continue; }

            if (isset($status->ERROR) || empty($status->StatusNET->IPAddress)) { $result->offlineTopics[]=['mqttTopic'=>$mqttTopic,'reason'=>'invalid-status-response']; continue; }
            $addressKey=$this->buildAddressKey((string)$status->StatusNET->IPAddress,$request->httpPort);
            if(isset($claimedAddresses[$addressKey])&&$claimedAddresses[$addressKey]!==$mqttTopic){$result->conflicts[]=['mqttTopic'=>$mqttTopic,'reason'=>'address-already-claimed'];continue;}
            $devices=$this->deviceRepository->getDevices(); $existingDevice=$this->deviceRepository->getDeviceByMqttTopic($mqttTopic);
            if($existingDevice instanceof Device){$matches=$this->findAddressMatches($devices,(string)$status->StatusNET->IPAddress,$request->httpPort);$conflicts=array_values(array_filter($matches,static fn(Device $d):bool=>$d->id!==$existingDevice->id));if([]!==$conflicts){$result->conflicts[]=['mqttTopic'=>$mqttTopic,'reason'=>'topic-collides-with-other-device-address'];continue;}$result->updatedDevices[]=$this->refreshExistingDevice($existingDevice,$mqttTopic,$status,false,$request);$claimedAddresses[$addressKey]=$mqttTopic;continue;}
            $legacy=$this->findAddressMatches($devices,(string)$status->StatusNET->IPAddress,$request->httpPort);if(count($legacy)>1){$result->conflicts[]=['mqttTopic'=>$mqttTopic,'reason'=>'legacy-address-ambiguous'];continue;}if(1===count($legacy)){$result->updatedDevices[]=$this->refreshExistingDevice($legacy[0],$mqttTopic,$status,true,$request);$claimedAddresses[$addressKey]=$mqttTopic;continue;}
            $status->OpenBKAdminMqttTopic=$mqttTopic;$status->OpenBKAdminDevicePort=$request->httpPort;$result->newDevices[]=$status;$claimedAddresses[$addressKey]=$mqttTopic;
        }
        return $result;
    }

    private function findAddressMatches(array $devices,string $ip,int $port):array{$matches=[];foreach($devices as $device)if($device->ip===$ip&&$device->port===$port)$matches[]=$device;return$matches;}
    private function refreshExistingDevice(Device $device,string $mqttTopic,\stdClass $status,bool $backfilledTopic,MqttDiscoveryRequest $request):array{$oldIp=$device->ip;$device->ip=(string)$status->StatusNET->IPAddress;$device->mqttTopic=$mqttTopic;$this->deviceRepository->updateDevice($device);return['deviceId'=>$device->id,'mqttTopic'=>$mqttTopic,'oldIp'=>$oldIp,'newIp'=>$device->ip,'port'=>$device->port,'backfilledTopic'=>$backfilledTopic?'1':'0','name'=>$device->getName()];}
    private function extractDiscoveryTopic(string $topic,string $telePrefix):?string{return$this->extractTopicForPrefix($topic,$telePrefix);}
    private function extractStatusTopic(string $topic,string $statPrefix):?string{return$this->extractTopicForPrefix($topic,$statPrefix,'STATUS0');}
    private function extractNativeBaseTopic(string $topic,string $suffix):?string{$topic=trim($topic,'/');$suffix=trim($suffix,'/');$needle='/'.$suffix;if(strlen($topic)<=strlen($needle)||substr($topic,-strlen($needle))!==$needle)return null;$base=trim(substr($topic,0,-strlen($needle)),'/');return''!==$base?$base:null;}
    private function extractTopicForPrefix(string $topic,string $prefix,?string $requiredSuffix=null):?string{$topicParts=$this->splitTopic($topic);$prefixParts=$this->splitTopic($prefix);if(count($topicParts)<=count($prefixParts))return null;foreach($prefixParts as $i=>$part)if(!isset($topicParts[$i])||$topicParts[$i]!==$part)return null;$suffix=array_pop($topicParts);if(null!==$requiredSuffix&&strtoupper((string)$suffix)!==strtoupper($requiredSuffix))return null;$mqttTopic=trim(implode('/',array_slice($topicParts,count($prefixParts))));return''!==$mqttTopic?$mqttTopic:null;}
    private function buildTopic(string $prefix,string $mqttTopic,string $suffix):string{return sprintf('%s/%s/%s',trim($prefix,'/'),trim($mqttTopic,'/'),trim($suffix,'/'));}
    private function buildPrefixWildcardTopic(string $prefix):string{return sprintf('%s/#',trim($prefix,'/'));}
    private function buildAddressKey(string $ip,int $port):string{return sprintf('%s:%d',$ip,$port);}
    private function splitTopic(string $topic):array{return array_values(array_filter(explode('/',trim($topic,'/')),static fn(string $part):bool=>''!==$part));}
}
