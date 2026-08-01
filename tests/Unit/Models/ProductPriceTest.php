<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ProductPrice;

test('to array', function (): void {
    $productPrice = ProductPrice::factory()->create()->fresh();

    expect(array_keys($productPrice->toArray()))
        ->toBe([
            'id',
            'product_id',
            'created_by',
            'price',
            'effective_at',
            'expires_at',
            'created_at',
            'updated_at',
        ]);
});
