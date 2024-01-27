<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Model;

trait AssertsDatabase
{
    /** データベースの内容をアサーションする */
    protected function assertDatabaseIs(string $table, array $names, array $records): void
    {
        $this->assertDatabaseCount($table, count($records));
        foreach ($records as $recordOrModel) {
            $data = $recordOrModel instanceof Model ? $recordOrModel->only($names) : array_combine($names, $recordOrModel);
            $this->assertDatabaseHas($table, $data);
        }
    }

    /** shift_begins テーブルの内容をアサーションする */
    protected function assertShiftBegins(array $records): void
    {
        $this->assertDatabaseIs('shift_begins', ['user_id', 'begun_at'], $records);
    }

    /** shift_timings テーブルの内容をアサーションする */
    protected function assertShiftTimings(array $records): void
    {
        $this->assertDatabaseIs('shift_timings', ['user_id', 'begun_at', 'ended_at'], $records);
    }

    /** break_begins テーブルの内容をアサーションする */
    protected function assertBreakBegins(array $records): void
    {
        $this->assertDatabaseIs('break_begins', ['user_id', 'begun_at'], $records);
    }

    /** break_timings テーブルの内容をアサーションする */
    protected function assertBreakTimings(array $records): void
    {
        $this->assertDatabaseIs('break_timings', ['user_id', 'begun_at', 'ended_at'], $records);
    }
}
