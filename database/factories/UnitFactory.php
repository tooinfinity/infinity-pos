<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
final class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Piece',
            'symbol' => 'pcs',
            'is_active' => true,
        ];
    }

    public function kilogram(): self
    {
        return $this->state(fn (): array => [
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'is_active' => true,
        ]);
    }

    public function liter(): self
    {
        return $this->state(fn (): array => [
            'name' => 'Liter',
            'symbol' => 'L',
            'is_active' => true,
        ]);
    }
}
