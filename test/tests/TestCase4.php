<?php

namespace CHMLib\Test;

abstract class TestCase4 extends TestCaseBase
{
    public static function setupBeforeClass()
    {
        static::doSetUpBeforeClass();
    }

    public function setUp()
    {
        static::doSetUp();
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function assertStringNotContainsString($needle, $haystack, $message = '')
    {
        static::assertNotContains($needle, $haystack, $message);
    }

    public static function assertMatchRegExp($pattern, $string, $message = '')
    {
        static::assertRegExp($pattern, $string, $message);
    }
}
