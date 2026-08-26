<?php

namespace OpenBKAdmin\Helper;

use GuzzleHttp\Client;
use OpenBKAdmin\Config;

class GuzzleFactory
{
    public static function getClient(Config $config): Client
    {
        return new Client(['headers' => [
            'User-Agent' => "OpenBKAdmin/{$config->read('current_git_tag')}",
        ]]);
    }
}
