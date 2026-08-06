<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Wizard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'name' => fake()->words(3, true),
            'required_questions' => [],
        ];
    }
}
