<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\BreakTiming;
use App\Models\ShiftTiming;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\UserController
 *
 * @group users
 */
class UserControllerShowTest extends TestCase
{
    use RefreshDatabase;

    /** ログインに使用する会員 */
    private User $user;

    /** 会員と勤怠情報を作成する */
    protected function setUp(): void
    {
        parent::setUp();

        $today = CarbonImmutable::create(year: 2024, month: 2, day: 8, tz: 'Asia/Tokyo');

        $this->user = User::factory()->create();
        foreach ($today->subMonth()->firstOfMonth()->daysUntil($today->subDay()) as $date) {
            $this->travelTo($date, function () use ($date) {
                if ($date->isWeekday()) {
                    ShiftTiming::factory()->recycle($this->user)->create();
                    BreakTiming::factory(2)->recycle($this->user)->create();
                }
            });
        }

        CarbonImmutable::setTestNow($today);
    }

    /**
     * @testdox [GET /users/{id}] [非認証状態] route('login') へリダイレクトする
     */
    public function testGetUserFromGuestRedirectsToLoginPage(): void
    {
        $response = $this->get(route('users.show', ['user' => $this->user->id]));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [GET /users/{id}] [認証済み] ステータスコード200を返す
     */
    public function testGetUserReturnsStatusCode200(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.show', ['user' => $this->user->id]));
        $response->assertStatus(200);
    }

    /**
     * @testdox [GET /users/{id}] [認証済み] viewData('userName') に会員名を含む
     */
    public function testGetUserReturnsUserNameInViewData(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.show', ['user' => $this->user->id]));
        $response->assertViewHas('userName', $this->user->name);
    }

    /**
     * @testdox [GET /users/{id}] [認証済み] viewData('currentMonth') に現在の年月の初日を含む
     */
    public function testGetUserReturnsCurrentMonthInViewData(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.show', ['user' => $this->user->id]));
        $response->assertViewHas('currentMonth', CarbonImmutable::today()->firstOfMonth());
    }

    /**
     * @testdox [GET /users/{id}] [認証済み] viewData('attendances') に当月1日から5日までの勤怠情報を含む
     */
    public function testGetUserReturnsAttendancesInViewData(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.show', ['user' => $this->user->id]));
        $response->assertViewHas('attendances');
        $attendances = $response->viewData('attendances');
        $this->assertCount(5, $attendances);
        $firstOfCurrentMonth = CarbonImmutable::today()->firstOfMonth();
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($firstOfCurrentMonth->addDays($i), $attendances[$i]->date);
        }
    }

    /**
     * @testdox [GET /users/{id}?page=2] [認証済み] viewData('attendances') に当月6日から8日までの勤怠情報を含む
     */
    public function testGetUserReturnsAttendancesFromPageTwoInViewData(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.show', ['user' => $this->user->id, 'page' => 2]));
        $response->assertViewHas('attendances');
        $attendances = $response->viewData('attendances');
        $this->assertCount(3, $attendances);
        $fifthOfCurrentMonth = CarbonImmutable::today()->firstOfMonth()->addDays(5);
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($fifthOfCurrentMonth->addDays($i), $attendances[$i]->date);
        }
    }

    /**
     * @testdox [GET /users/{id}?ym=2024-01] [認証済み] viewData('attendances') に前月1日から5日までの勤怠情報を含む
     */
    public function testGetUserReturnsAttendancesFromPreviousMonthInViewData(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.show', ['user' => $this->user->id, 'ym' => '2024-01']));
        $response->assertViewHas('attendances');
        $attendances = $response->viewData('attendances');
        $this->assertCount(5, $attendances);
        $firstOfPreviousMonth = CarbonImmutable::today()->subMonth()->firstOfMonth();
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($firstOfPreviousMonth->addDays($i), $attendances[$i]->date);
        }
    }
}
