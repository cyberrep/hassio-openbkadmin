<?php

use Selective\Container\Container;
use OpenBKAdmin\Backup\BackupHelper;
use OpenBKAdmin\Config;
use OpenBKAdmin\DevicePasswordCipher;
use OpenBKAdmin\DevicePasswordKeyProvider;
use OpenBKAdmin\DeviceRepository;
use OpenBKAdmin\Helper\RedirectHelper;
use OpenBKAdmin\Helper\UrlHelper;
use OpenBKAdmin\Helper\ViewHelper;
use OpenBKAdmin\Http\HttpClientFactory;
use OpenBKAdmin\Mqtt\MqttDiscoveryService;
use OpenBKAdmin\Mqtt\PhpMqttClientFactory;
use OpenBKAdmin\Mqtt\SystemTimeProvider;
use OpenBKAdmin\OpenBeken;
use OpenBKAdmin\OpenBeken\ResponseParser;

$container = new Container();

$container->set(Config::class, new Config(_DATADIR_, _APPROOT_));
$container->set(
    UrlHelper::class,
    new UrlHelper(
        $container->get(Config::class),
        _RESOURCESURL_,
        _RESOURCESDIR_
    )
);
$container->set(HttpClientFactory::class, new HttpClientFactory($container->get(Config::class)));
$container->set(DevicePasswordKeyProvider::class, new DevicePasswordKeyProvider(_DATADIR_));
$container->set(DevicePasswordCipher::class, new DevicePasswordCipher($container->get(DevicePasswordKeyProvider::class)));
$container->set(ResponseParser::class, new ResponseParser());
$container->set(DeviceRepository::class, new DeviceRepository(
    _CSVFILE_,
    _TMPDIR_,
    $container->get(DevicePasswordCipher::class),
    '1' === $container->get(Config::class)->read('confirm_device_toggles')
));
$container->set(OpenBeken::class, new OpenBeken(
    $container->get(DeviceRepository::class),
    $container->get(HttpClientFactory::class)->getClient(),
    $container->get(Config::class)
));
$container->set(MqttDiscoveryService::class, new MqttDiscoveryService(
    $container->get(DeviceRepository::class),
    $container->get(ResponseParser::class),
    new PhpMqttClientFactory(),
    new SystemTimeProvider()
));
$container->set(i18n::class, new i18n());
$container->set(BackupHelper::class, new BackupHelper(
    $container->get(DeviceRepository::class),
    $container->get(OpenBeken::class),
    _TMPDIR_.'backups/',
    $container->get(Config::class),
    _BASEURL_
));
$container->set(ViewHelper::class, new ViewHelper($container->get(Config::class)));
$container->set(RedirectHelper::class, new RedirectHelper(_BASEURL_));

return $container;
