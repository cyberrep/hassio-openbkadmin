<?php

namespace Tests\OpenBKAdmin\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use OpenBKAdmin\Helper\RedirectHelper;

class RedirectHelperTest extends TestCase
{
    #[DataProvider('validDataProvider')]
    public function testGetValidRedirectUrlValid(string $basePath, string $url, string $fallbackUrl): void
    {
        $redirectHelper = new RedirectHelper($basePath);
        self::assertEquals($url, $redirectHelper->getValidRedirectUrl($url, $fallbackUrl));
    }

    public static function validDataProvider(): array
    {
        return [
            ['/', '/foo/bar', '/'],
            ['/OpenBKAdmin', '/OpenBKAdmin/devices', '/OpenBKAdmin/start'],
        ];
    }

    #[DataProvider('invalidDataProvider')]
    public function testGetValidRedirectUrlInvalid(string $url): void
    {
        $fallbackUrl = '/';
        $redirectHelper = new RedirectHelper('/');
        self::assertEquals($fallbackUrl, $redirectHelper->getValidRedirectUrl($url, $fallbackUrl));
    }

    public static function invalidDataProvider(): array
    {
        return [
            ['http://bad.com/foo/bar'],
            ['//bad.com/foo/bar'],
            ['/\bad.com/foo/bar'],
            ['://bad.com/foo/bar'],
        ];
    }

    public function testGetValidRedirectUrlRejectsUrlsOutsideConfiguredBasePath(): void
    {
        $fallbackUrl = '/OpenBKAdmin/start';
        $redirectHelper = new RedirectHelper('/OpenBKAdmin');

        self::assertSame($fallbackUrl, $redirectHelper->getValidRedirectUrl('/other/path', $fallbackUrl));
    }
}
