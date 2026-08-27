<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ActionsPhpSyntaxV065Test extends TestCase
{
    public function testNativeOtaHasSafetyChecks(): void
    {
        $source = file_get_contents(__DIR__.'/../../pages/actions.php');
        self::assertStringContainsString("in_array(\$scheme, ['http', 'https'], true)", $source);
        self::assertStringContainsString("'Content-Length' => (string) \$size", $source);
        self::assertStringContainsString("RequestOptions::AUTH", $source);
    }
}
