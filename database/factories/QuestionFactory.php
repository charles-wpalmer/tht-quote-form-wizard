<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Wizard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wizard_id' => Wizard::factory(),
            'label' => fake()->sentence(4),
            'type' => QuestionType::Text,
            'options' => null,
            'is_required' => true,
            'sort' => 0,
        ];
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Select,
            'options' => ['Option A', 'Option B', 'Option C'],
        ]);
    }

    public function radio(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Radio,
            'options' => ['Yes', 'No', 'Maybe'],
        ]);
    }

    public function checkbox(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Checkbox,
            'options' => ['Feature A', 'Feature B', 'Feature C'],
        ]);
    }
}
