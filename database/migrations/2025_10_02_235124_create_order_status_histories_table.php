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
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->userstamps();
            $table->text('note')->nullable();

            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('order_status_id')->constrained('order_statuses');

            $table->unique(['order_id', 'order_status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
