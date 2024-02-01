<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;

class AttendanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** テスト実行時に固定する日付 */
    private CarbonImmutable $today;

    /** テスト実行時に固定する日付の前日 */
    private CarbonImmutable $yesterday;

    /** 認証に使用するユーザ */
    private User $user;

    /** テスト開始時に会員103名と勤怠データを作成する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->today = CarbonImmutable::create(year: 2024, month: 1, day: 24, tz: 'Asia/Tokyo');
        $this->travelTo($this->today, function () {
            $seeder = new \Database\Seeders\DatabaseSeeder();
            $seeder->run();
        });
        $this->user = User::first();
    }

    /** attendanceへGETリクエストを送る。 */
    private function getAttendance(?string $date = null, ?string $page = null): TestResponse
    {
        return $this->travelTo(
            $this->today,
            fn () => $this
                ->actingAs($this->user)
                ->get(route('attendance', ['date' => $date, 'page' => $page])),
        );
    }

    /**
     * @testdox [GET attendance] [非認証状態] route('login') へリダイレクトする
     * @group attendance
     */
    public function testGetAttendanceFromGuestReturnsRedirectToRouteLogin(): void
    {
        $response = $this->get(route('attendance'));
        $response->assertRedirectToRoute('login');
    }

    /**
     * @testdox [GET attendance] [認証状態] ステータスコード200が返ってくる
     * @group attendance
     */
    public function testGetAttendanceFromAuthenticatedUserReturnsStatusCode200(): void
    {
        $response = $this->getAttendance();
        $response->assertStatus(200);
    }

    /**
     * @testdox [GET attendance] [認証状態] date=$dateString&page=$pageString
     * @group attendance
     * @testWith [null        , null  ]
     *           ["2024-01-22", null  ]
     *           ["2024-01-23", null  ]
     *           ["2024-01-24", null  ]
     *           ["2024-01-25", null  ]
     *           ["test"      , null  ]
     *           [null        , "0"   ]
     *           [null        , "1"   ]
     *           [null        , "2"   ]
     *           [null        , "20"  ]
     *           [null        , "21"  ]
     *           [null        , "22"  ]
     *           [null        , "test"]
     */
    public function testGetAttendanceFromAuthenticatedUser(?string $dateString, ?string $pageString): void
    {
        try {
            $date = $dateString ? CarbonImmutable::parse($dateString, 'Asia/Tokyo') : $this->today;
        } catch (InvalidFormatException $e) {
            $date = $this->today;
        }

        $page = 1;
        if (is_numeric($pageString)) {
            $page = intval($pageString);
        }
        if ($page < 1) {
            $page = 1;
        }

        $users = User::all()
            ->filter(fn (User $user) => $user->isWorkingOn($date))
            ->sortBy(['name', 'id'])
            ->skip(5 * ($page - 1))
            ->take(5);

        $response = $this->getAttendance($dateString, $pageString);

        $this->assertEquals($date, $response['currentDate']);

        $attendances = $response['attendances'];
        $this->assertSameSize($users, $attendances);
        foreach ($attendances->zip($users) as [$attendance, $user]) {
            $expected = ['user' => $user->toArray(), 'shift_begins' => $user->shiftBegin()->get()->toArray(), 'shift_timings' => $user->shiftTimings()->get()->toArray()];
            $message = 'expected: ' . var_export($expected, true) . PHP_EOL . 'actual: ' . var_export($attendance->toArray(), true);
            $this->assertSame($user->name, $attendance['user_name'], $message);
            $this->assertSameDateTime($user->shiftBegunAtDate($date), $attendance['shift_begun_at'], $message);
            $this->assertSameDateTime($user->shiftEndedAtDate($date), $attendance['shift_ended_at'], $message);
            $this->assertSame($user->breakTimeInSeconds($date), $attendance['break_seconds'], $message);
            $this->assertSame($user->workTimeInSeconds($date), $attendance['work_seconds'], $message);
        }
    }
}
