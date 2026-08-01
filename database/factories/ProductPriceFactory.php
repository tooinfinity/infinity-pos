<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
final class ProductPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'created_by' => User::factory(),
            'price' => fake()->numberBetween(50, 50000),
            'effective_at' => now(),
            'expires_at' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'effective_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
        ]);
    }

    public function future(): self
    {
        return $this->state(fn (): array => [
            'effective_at' => now()->addWeek(),
            'expires_at' => null,
        ]);
    }
}
