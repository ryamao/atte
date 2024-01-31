<?php

namespace Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** 日時の文字列をアサーションする */
    protected function assertSameDateTime(?CarbonImmutable $expected, ?string $actual, string $message = ''): void
    {
        if (is_null($expected)) {
            $this->assertNull($actual, $message);
        } else {
            $this->assertSame($expected->toDateTimeString(), $actual, $message);
        }
    }

    /** データベースから返ってきた秒数をアサーションする */
    protected function assertSameSeconds(?int $expected, int|string|null $actual, string $message = ''): void
    {
        if (is_null($expected)) {
            $this->assertNull($actual, $message);
        } else if (is_integer($actual)) {
            $this->assertSame($expected, $actual, $message);
        } else {
            $this->assertSame((string) $expected, $actual, $message);
        }
    }
}
