<?php

namespace Tests\Feature;

use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StampTest extends TestCase
{
    use RefreshDatabase;

    private User $loginUser;
    private CarbonImmutable $testDate;

    /** 各テストケース前に実行する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginUser = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->testDate = CarbonImmutable::create(2024, 1, 24, 9, 0, 0, new DateTimeZone('Asia/Tokyo'));
    }

    /**
     * @testdox [GET stamp] [未認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_get_index_for_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->get(route('stamp'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [GET stamp] [認証状態] ステータスコード200を返す
     * @group stamp
     */
    public function test_get_index_for_auth_user_returns_status_code_200(): void
    {
        $response = $this->actingAs($this->loginUser)->get(route('stamp'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [POST shift-begin] [未認証状態] route(login) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_begin_for_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->post(route('shift-begin'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST shift-begin] [認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_redirects_to_index_page(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('shift-begin'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前] shift_begins テーブルに現在日時を保存する
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_stores_current_datetime_to_shift_begins_table_before_working(): void
    {
        $this->travelTo($this->testDate, function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));
            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertEquals($this->testDate, ShiftBegin::first()->begun_at);
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前] shift_timings テーブルに変化がない
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_do_nothing_to_shift_timings_table_before_working(): void
    {
        $this->travelTo($this->testDate, function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));
            $this->assertDatabaseEmpty('shift_timings');
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前] 前日に勤務時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_before_working_when_shift_timings_is_recorded_on_previous_day(): void
    {
        $begunAt = $this->testDate->subDay();
        $endedAt = $begunAt->addHours(8);
        ShiftTiming::create(['user_id' => $this->loginUser->id, 'begun_at' => $begunAt, 'ended_at' => $endedAt]);

        $this->travelTo($this->testDate, function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));

            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertEquals($this->testDate, ShiftBegin::first()->begun_at);

            $this->assertDatabaseCount('shift_timings', 1);
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務中] shift_begins テーブルに変化がない
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_do_nothing_to_shift_begins_table_while_at_work(): void
    {
        $shiftBegin = ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $this->travelTo($this->testDate->addHour(), function () use ($shiftBegin) {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));
            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertObjectEquals($shiftBegin, ShiftBegin::first());
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務中] shift_timings テーブルに変化がない
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_do_nothing_to_shift_timings_table_while_at_work(): void
    {
        ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $this->travelTo($this->testDate->addHour(), function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));
            $this->assertDatabaseEmpty('shift_timings');
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務中] 前日に勤務時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_while_at_work_when_shift_timings_is_recorded_on_previous_day(): void
    {
        $begunAt = $this->testDate->subDay();
        $endedAt = $begunAt->addHours(8);
        ShiftTiming::create(['user_id' => $this->loginUser->id, 'begun_at' => $begunAt, 'ended_at' => $endedAt]);

        ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $this->travelTo($this->testDate->addHour(), function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));

            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertEquals($this->testDate, ShiftBegin::first()->begun_at);

            $this->assertDatabaseCount('shift_timings', 1);
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務後] 勤務再開処理を実行する
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_do_nothing_to_shift_begins_table_after_working(): void
    {
        $endedAt = $this->testDate->addHours(8);
        ShiftTiming::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate, 'ended_at' => $endedAt]);

        $this->travelTo($endedAt->addHour(), function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));

            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertEquals($this->testDate, ShiftBegin::first()->begun_at);

            $this->assertDatabaseEmpty('shift_timings');
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務後] 前日に勤務時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_after_working_when_shift_timings_is_recorded_on_previous_day(): void
    {
        $begunAt = $this->testDate->subDay();
        $endedAt = $begunAt->addHours(8);
        ShiftTiming::create(['user_id' => $this->loginUser->id, 'begun_at' => $begunAt, 'ended_at' => $endedAt]);

        $endedAt = $this->testDate->addHours(8);
        ShiftTiming::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate, 'ended_at' => $endedAt]);

        $this->travelTo($endedAt->addHour(), function () {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));

            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertEquals($this->testDate, ShiftBegin::first()->begun_at);

            $this->assertDatabaseCount('shift_timings', 1);
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [複数ユーザ] shift_begins テーブルに現在日時を保存する
     * @group stamp
     */
    public function test_post_shift_begin_can_store_to_shift_begins_table_if_another_users_are_working(): void
    {
        ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $this->travelTo($this->testDate, function () {
            $anotherUser = User::create([
                'name' => 'test2',
                'email' => 'test2@example.com',
                'password' => Hash::make('password2'),
            ]);

            $this->actingAs($anotherUser)->post(route('shift-begin'));
            $this->assertDatabaseCount('shift_begins', 2);
            $this->assertEquals(
                $this->testDate,
                ShiftBegin::where('user_id', $anotherUser->id)->first()->begun_at
            );
        });
    }

    /**
     * @testdox [POST shift-begin] [認証状態] 日付を跨いだ場合
     * @group stamp
     */
    public function test_post_shift_begin_for_auth_user_when_dates_are_crossed(): void
    {
        ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $nextDay = $this->testDate->addDay();
        $this->travelTo($nextDay, function () use ($nextDay) {
            $this->actingAs($this->loginUser)->post(route('shift-begin'));

            $this->assertDatabaseCount('shift_begins', 1);
            $this->assertEquals($nextDay, ShiftBegin::first()->begun_at);

            $this->assertDatabaseCount('shift_timings', 1);
            $shiftTiming = ShiftTiming::first();
            $this->assertEquals($this->testDate, $shiftTiming->begun_at);
            $this->assertNull($shiftTiming->ended_at);
        });
    }

    /**
     * @testdox [POST shift-end] [未認証状態] route(login) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_end_for_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->post(route('shift-end'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST shift-end] [認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_end_for_auth_user_redirects_to_index_page(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('shift-end'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務前] shift_begins に変化がない
     * @group stamp
     */
    public function test_post_shift_end_for_auth_user_do_nothing_to_shift_begins_table_before_working(): void
    {
        $this->travelTo($this->testDate, function () {
            $this->actingAs($this->loginUser)->post(route('shift-end'));
            $this->assertDatabaseEmpty('shift_begins');
        });
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務前] shift_timings に変化がない
     * @group stamp
     */
    public function test_post_shift_end_for_auth_user_do_nothing_to_shift_timings_table_before_working(): void
    {
        $this->travelTo($this->testDate, function () {
            $this->actingAs($this->loginUser)->post(route('shift-end'));
            $this->assertDatabaseEmpty('shift_timings');
        });
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務中] 勤務終了処理を実行する
     * @group stamp
     */
    public function test_post_shift_end_for_auth_user_stores_shift_timing_while_at_work(): void
    {
        ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $endedAt = $this->testDate->addHours(8);
        $this->travelTo($endedAt, function () use ($endedAt) {
            $this->actingAs($this->loginUser)->post(route('shift-end'));
            $this->assertDatabaseEmpty('shift_begins');
            $this->assertDatabaseCount('shift_timings', 1);
            $shiftTiming = ShiftTiming::first();
            $this->assertEquals($this->testDate, $shiftTiming->begun_at);
            $this->assertEquals($endedAt, $shiftTiming->ended_at);
        });
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務後] shift_timings に変化がない
     * @group stamp
     */
    public function test_post_shift_end_for_auth_user_stores_shift_timing_after_working(): void
    {
        $endedAt = $this->testDate->addHours(8);
        ShiftTiming::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate, 'ended_at' => $endedAt]);

        $this->travelTo($endedAt, function () use ($endedAt) {
            $this->actingAs($this->loginUser)->post(route('shift-end'));
            $this->assertDatabaseEmpty('shift_begins');
            $this->assertDatabaseCount('shift_timings', 1);
            $shiftTiming = ShiftTiming::first();
            $this->assertEquals($this->testDate, $shiftTiming->begun_at);
            $this->assertEquals($endedAt, $shiftTiming->ended_at);
        });
    }

    /**
     * @testdox [POST shift-end] [認証状態] 日付を跨いだ場合
     * @group stamp
     */
    public function test_post_shift_end_for_auth_user_when_dates_are_crossed(): void
    {
        ShiftBegin::create(['user_id' => $this->loginUser->id, 'begun_at' => $this->testDate]);

        $nextDay = $this->testDate->addDay();
        $this->travelTo($nextDay, function () use ($nextDay) {
            $this->actingAs($this->loginUser)->post(route('shift-end'));

            $this->assertDatabaseEmpty('shift_begins');

            $this->assertDatabaseCount('shift_timings', 1);
            $shiftTiming = ShiftTiming::first();
            $this->assertEquals($this->testDate, $shiftTiming->begun_at);
            $this->assertNull($shiftTiming->ended_at);
        });
    }
}
