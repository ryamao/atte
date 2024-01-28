<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
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
        $now = CarbonImmutable::now();
        $shiftSeconds = 9 * 60 * 60 + $this->faker->numberBetween(0, 1 * 60 * 60);
        return [
            'user_id' => User::factory(),
            'begun_at' => $now,
            'ended_at' => $now->addSeconds($shiftSeconds),
        ];
    }
}
