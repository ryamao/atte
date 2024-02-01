<?php

declare(strict_types=1);

namespace Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** 同じ日時であることをアサーションする */
    protected function assertSameDateTime(?CarbonImmutable $expected, ?CarbonImmutable $actual, string $message = ''): void
    {
        if (is_null($expected)) {
            $this->assertNull($actual, $message);
        } else {
            $this->assertTrue($expected->diffInSeconds($actual) === 0, $message);
        }
    }
}
