<?php

declare(strict_types=1);

use App\Models\Unit;

test('to array', function (): void {
    $unit = Unit::factory()->create()->fresh();

    expect(array_keys($unit->toArray()))
        ->toBe([
            'id',
            'name',
            'symbol',
            'is_active',
            'created_at',
            'updated_at',
        ]);
});
