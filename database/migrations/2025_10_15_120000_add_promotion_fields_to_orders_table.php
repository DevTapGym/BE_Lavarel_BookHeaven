<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'promotion_id')) {
                $table->foreignId('promotion_id')->nullable()->constrained('promotions');
            }
            if (!Schema::hasColumn('orders', 'total_promotion_value')) {
                $table->decimal('total_promotion_value', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'promotion_id')) {
                $table->dropConstrainedForeignId('promotion_id');
            }
            if (Schema::hasColumn('orders', 'total_promotion_value')) {
                $table->dropColumn('total_promotion_value');
            }
        });
    }
};


