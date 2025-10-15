<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 100)->nullable();
            }
            if (Schema::hasColumn('orders', 'payment_method_id')) {
                $table->dropConstrainedForeignId('payment_method_id');
            }
            if (Schema::hasColumn('orders', 'shipping_address_id')) {
                $table->dropConstrainedForeignId('shipping_address_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (!Schema::hasColumn('orders', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->constrained('payment_methods');
            }
            if (!Schema::hasColumn('orders', 'shipping_address_id')) {
                $table->foreignId('shipping_address_id')->constrained('shipping_addresses');
            }
        });
    }
};


