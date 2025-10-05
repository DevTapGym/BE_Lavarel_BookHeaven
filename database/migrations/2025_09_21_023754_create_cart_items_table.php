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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_selected')->default(true);

            $table->foreignId('cart_id')->constrained('carts');
            $table->foreignId('book_id')->constrained('books');
            $table->unique(['cart_id', 'book_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
