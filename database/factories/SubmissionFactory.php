<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Wizard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wizard_id' => Wizard::factory(),
            'answers' => [
                '1' => fake()->sentence(),
            ],
        ];
    }
}
