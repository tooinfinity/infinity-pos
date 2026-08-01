<?php

declare(strict_types=1);

use App\Models\Category;

test('to array', function (): void {
    $category = Category::factory()->create()->fresh();

    expect(array_keys($category->toArray()))
        ->toBe([
            'id',
            'name',
            'slug',
            'is_active',
            'created_at',
            'updated_at',
        ]);
});
