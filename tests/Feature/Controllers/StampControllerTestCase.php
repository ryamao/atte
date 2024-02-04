<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\BreakTiming;
use App\Models\ShiftTiming;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\AssertsDatabase;
use Tests\TestCase;

class StampControllerTestCase extends TestCase
{
    use AssertsDatabase;

    /** テスト中の認証に使用するユーザ */
    protected User $loginUser;

    /** この日時を基準にして相対時刻でテストする */
    protected CarbonImmutable $testBegunAt;

    /** 認証ユーザと基準時刻を用意する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testBegunAt = CarbonImmutable::create(2024, 1, 24, 9, 0, 0, new DateTimeZone('Asia/Tokyo'));

        $this->loginUser = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->loginUser->markEmailAsVerified();
    }

    /** 勤務時間をデータベースに保存する。 */
    protected function createShiftTiming(?User $user = null, ?CarbonImmutable $begunAt = null, int $hours = 8): ShiftTiming
    {
        $user = $user ?? $this->loginUser;
        $begunAt = $begunAt ?? $this->testBegunAt;

        return ShiftTiming::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
            'ended_at' => $begunAt->addHours($hours),
        ]);
    }

    /** 休憩時間をデータベースに保存する。 */
    protected function createBreakTiming(?User $user = null, ?CarbonImmutable $begunAt = null, int $minutes = 60): BreakTiming
    {
        $user = $user ?? $this->loginUser;
        $begunAt = $begunAt ?? $this->testBegunAt;

        return BreakTiming::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
            'ended_at' => $begunAt->addMinutes($minutes),
        ]);
    }

    /** 指定のルートにログイン状態でPOSTを送信する。送信時の日時を指定できる。指定がない場合は現在日時を使用する。 */
    protected function loginAndPost(string $routeName, ?DateTimeInterface $when = null): TestResponse
    {
        $datetime = $when ?? $this->testBegunAt;

        return $this->travelTo(
            $datetime,
            fn () => $this->actingAs($this->loginUser)->post(route($routeName))
        );
    }
}
