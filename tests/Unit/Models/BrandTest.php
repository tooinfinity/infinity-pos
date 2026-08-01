<?php

declare(strict_types=1);

use App\Models\Brand;

test('to array', function (): void {
    $brand = Brand::factory()->create()->fresh();

    expect(array_keys($brand->toArray()))
        ->toBe([
            'id',
            'name',
            'slug',
            'is_active',
            'created_at',
            'updated_at',
        ]);
});
