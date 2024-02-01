<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable as DT;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftBegin>
 */
class ShiftBeginFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $today = DT::today('Asia/Tokyo');

        return [
            'user_id' => User::factory(),
            'begun_at' => DT::make($this->faker->dateTimeInInterval($today->hour(9), '+2 hours')),
        ];
    }
}
