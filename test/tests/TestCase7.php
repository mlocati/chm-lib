<?php

namespace CHMLib\Test;

abstract class TestCase7 extends TestCaseBase
{
    public static function setupBeforeClass(): void
    {
        static::doSetUpBeforeClass();
    }

    public function setUp(): void
    {
        static::doSetUp();
    }

    public static function assertMatchRegExp(string $pattern, string $string, string $message = ''): void
    {
        static::assertRegExp($pattern, $string, $message);
    }
}
