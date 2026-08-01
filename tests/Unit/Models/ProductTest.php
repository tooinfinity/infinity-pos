<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Product;

test('to array', function (): void {
    $product = Product::factory()->create()->fresh();

    expect(array_keys($product->toArray()))
        ->toBe([
            'id',
            'category_id',
            'unit_id',
            'brand_id',
            'sku',
            'barcode',
            'name',
            'is_active',
            'image',
            'created_at',
            'updated_at',
        ]);
});
