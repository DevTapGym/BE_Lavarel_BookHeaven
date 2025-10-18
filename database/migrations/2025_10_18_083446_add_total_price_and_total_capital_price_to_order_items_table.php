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
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('total_price', 15, 2)->default(0)->after('capital_price')->comment('Tổng giá bán = price × quantity');
            $table->decimal('total_capital_price', 15, 2)->default(0)->after('total_price')->comment('Tổng giá vốn = capital_price × quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['total_price', 'total_capital_price']);
        });
    }
};
