<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('barcode')->nullable()->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('unit', 20)->default('piece');

            // All money in centimes (DZD). Never float.
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('retail_price')->default(0);
            $table->unsignedBigInteger('wholesale_price')->default(0);
            $table->unsignedBigInteger('min_price')->default(0);

            $table->decimal('min_stock', 12, 3)->default(0);
            $table->string('image_path')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
