<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakBegin>
 */
class BreakBeginFactory extends Factory
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
            'begun_at' => $now->subSeconds($this->faker->numberBetween(0, 10 * 60)),
        ];
    }
}
