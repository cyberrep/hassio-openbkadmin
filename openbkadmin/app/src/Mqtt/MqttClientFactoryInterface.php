<?php

namespace OpenBKAdmin\Mqtt;

interface MqttClientFactoryInterface
{
    public function create(string $host, int $port): MqttClientInterface;
}
