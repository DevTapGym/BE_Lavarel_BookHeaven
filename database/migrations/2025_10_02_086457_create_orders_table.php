<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->userstamps();
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->string('note')->nullable();
            $table->decimal('shipping_fee', 10, 2)->default(0);

            $table->foreignId('shipping_address_id')->constrained('shipping_addresses');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
