<?php

namespace Tests\OpenBKAdmin\Helper;

use PHPUnit\Framework\TestCase;
use OpenBKAdmin\Helper\HtmlAttributeHelper;

class HtmlAttributeHelperTest extends TestCase
{
    public function testSelectedReturnsHtmlAttributeWhenSelected(): void
    {
        self::assertSame('selected="selected"', HtmlAttributeHelper::selected(true));
    }

    public function testSelectedReturnsEmptyStringWhenNotSelected(): void
    {
        self::assertSame('', HtmlAttributeHelper::selected(false));
    }
}
