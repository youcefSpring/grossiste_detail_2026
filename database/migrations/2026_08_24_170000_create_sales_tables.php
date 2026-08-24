<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_wholesale')->default(false);   // drives the default price
            $table->bigInteger('balance')->default(0);         // centimes they owe us
            $table->unsignedBigInteger('credit_limit')->default(0); // 0 = no limit
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('phone');
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // null = walk-in
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();

            $table->string('type', 10)->default('retail');     // retail | wholesale
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('cost_total')->default(0); // snapshot, drives the profit figure
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->bigInteger('due_amount')->default(0);

            $table->string('status', 20)->default('completed'); // completed | voided
            $table->text('note')->nullable();
            $table->timestamp('sold_at');
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['sold_at', 'status']);
            $table->index('customer_id');
            $table->index(['user_id', 'sold_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');                    // snapshot
            $table->decimal('quantity', 12, 3);
            $table->decimal('returned_quantity', 12, 3)->default(0);
            $table->unsignedBigInteger('unit_price');          // snapshot
            $table->unsignedBigInteger('unit_cost');           // snapshot
            $table->unsignedBigInteger('line_total');
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};
