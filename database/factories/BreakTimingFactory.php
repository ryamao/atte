<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable as DT;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakTiming>
 */
class BreakTimingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $today = DT::today('Asia/Tokyo');
        $begin = DT::make($this->faker->dateTimeInInterval($today->hour(10), '+6 hours'));
        return [
            'user_id' => User::factory(),
            'begun_at' => $begin,
            'ended_at' => DT::make($this->faker->dateTimeInInterval($begin, '+1 hour')),
        ];
    }
}
