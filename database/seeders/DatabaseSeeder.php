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

        $firstOfPreviousMonth = CarbonImmutable::today()->subMonth()->firstOfMonth();

        $dates = $firstOfPreviousMonth->daysUntil(CarbonImmutable::today()->subDay());
        foreach ($dates as $date) {
            CarbonImmutable::setTestNow($date);

            foreach ($users as $user) {
                if (fake()->numberBetween(1, 7) <= 2) {
                    continue;
                }

                ShiftTiming::factory()->recycle($user)->create();
                BreakTiming::factory(fake()->numberBetween(1, 3))->recycle($user)->create();
            }
        }

        CarbonImmutable::setTestNow();

        foreach ($users as $user) {
            if (fake()->numberBetween(1, 7) <= 2) {
                continue;
            }

            ShiftBegin::factory()->recycle($user)->create();
            if (fake()->boolean()) {
                BreakBegin::factory()->recycle($user)->create();
            }
        }
    }
}
