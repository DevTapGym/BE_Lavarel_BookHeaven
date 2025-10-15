<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_histories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->userstamps();

            $table->foreignId('book_id')->constrained('books');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('import_receipt_id')->nullable()->constrained('import_receipts');

            $table->string('type', 50);
            $table->integer('qty_stock_before')->nullable();
            $table->integer('qty_change')->nullable();
            $table->integer('qty_stock_after')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_histories');
    }
};


