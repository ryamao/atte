<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
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
        $now = CarbonImmutable::now();
        return [
            'user_id' => User::factory(),
            'begun_at' => $now->addSeconds($this->faker->numberBetween(-10 * 60, 0)),
        ];
    }
}
