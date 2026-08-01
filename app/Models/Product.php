<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $id
 * @property-read string $category_id
 * @property-read string $unit_id
 * @property-read string $brand_id
 * @property-read string $sku
 * @property-read string|null $barcode
 * @property-read string $name
 * @property-read bool $is_active
 * @property-read string|null $image
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'category_id' => 'string',
            'unit_id' => 'string',
            'brand_id' => 'string',
            'sku' => 'string',
            'barcode' => 'string',
            'name' => 'string',
            'is_active' => 'boolean',
            'image' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
