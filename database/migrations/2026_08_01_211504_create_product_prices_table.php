<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuidFor(Product::class)->index();
            $table->foreignUuidFor(User::class, 'created_by')->index();

            $table->unsignedBigInteger('price');

            $table->timestamp('effective_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
