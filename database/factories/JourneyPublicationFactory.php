<?php

namespace Database\Factories;

use App\Enums\JourneyPublicationStatus;
use App\Models\JourneyPublication;
use App\Models\Wizard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JourneyPublication>
 */
class JourneyPublicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wizard_id' => Wizard::factory(),
            'version' => 1,
            'content' => null,
            'status' => JourneyPublicationStatus::Draft,
            'rollback' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'content' => [
                'name' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'questions' => [],
            ],
            'status' => JourneyPublicationStatus::Publish,
            'published_at' => now(),
        ]);
    }

    public function rollback(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rollback' => true,
        ]);
    }
}
