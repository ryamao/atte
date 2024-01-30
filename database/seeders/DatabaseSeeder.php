<?php

namespace Database\Seeders;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(103)->create();

        $dates = [CarbonImmutable::today(), CarbonImmutable::yesterday()];
        foreach ($dates as $i => $date) {
            CarbonImmutable::setTestNow($date);

            foreach ($users as $user) {
                if (fake()->boolean(5)) {
                    // 休み
                } else {
                    if ($i === 0 && fake()->boolean(10)) {
                        ShiftBegin::factory()->recycle($user)->create();

                        if (fake()->boolean()) {
                            BreakBegin::factory()->recycle($user)->create();
                        }
                    } else {
                        ShiftTiming::factory()->recycle($user)->create();
                    }

                    $breakCount = fake()->numberBetween(0, 2);
                    if ($breakCount > 0) {
                        BreakTiming::factory($breakCount)->recycle($user)->create();
                    }
                }
            }

            CarbonImmutable::setTestNow();
        }
    }
}
