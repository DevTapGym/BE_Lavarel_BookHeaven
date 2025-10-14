<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->userstamps();

            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('promotion_type')->nullable();
            $table->decimal('promotion_value', 12, 2)->nullable();
            $table->boolean('is_max_promotion_value')->default(false);
            $table->decimal('max_promotion_value', 12, 2)->nullable();
            $table->decimal('order_min_value', 12, 2)->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('qty_limit')->nullable();
            $table->boolean('is_once_per_customer')->default(false);
            $table->string('note')->nullable();

            $table->tinyInteger('is_deleted')->default(0);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};


