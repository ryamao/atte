<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable as DT;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftTiming>
 */
class ShiftTimingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $today = DT::today('Asia/Tokyo');
        $begin = DT::make($this->faker->dateTimeInInterval($today->hour(9), '+2 hours'));

        return [
            'user_id' => User::factory(),
            'begun_at' => $begin,
            'ended_at' => DT::make($this->faker->dateTimeInInterval($begin->addHours(9), '+2 hours')),
        ];
    }

    /** 定義する勤務時間データを勤務終了の打刻がない設定にする */
    public function unended(): ShiftTimingFactory
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
        ]);
    }
}
