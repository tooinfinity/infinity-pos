<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'brand_id' => Brand::factory(),
            'sku' => mb_strtoupper(fake()->bothify('SKU-######')),
            'barcode' => fake()->optional()->ean13(),
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function withoutBarcode(): self
    {
        return $this->state(fn (): array => [
            'barcode' => null,
        ]);
    }
}
