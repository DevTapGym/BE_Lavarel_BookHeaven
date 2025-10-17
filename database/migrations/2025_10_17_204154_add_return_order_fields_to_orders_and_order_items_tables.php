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
        // Add fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('type')->default('SALE')->after('order_number'); // SALE or RETURN
            $table->string('receiver_email')->nullable()->after('total_amount');
            $table->decimal('return_fee', 10, 2)->default(0)->after('total_promotion_value');
            $table->string('return_fee_type')->nullable()->after('return_fee'); // PERCENT or FIXED
            $table->decimal('total_refund_amount', 10, 2)->default(0)->after('return_fee_type');
            $table->string('vnp_txn_ref')->nullable()->after('total_refund_amount');
            $table->string('payment_status')->nullable()->after('vnp_txn_ref');
            $table->string('status')->nullable()->after('payment_status');
            $table->unsignedBigInteger('parent_id')->nullable()->after('status');
            $table->foreign('parent_id')->references('id')->on('orders')->onDelete('cascade');
        });

        // Add return_qty field to order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('return_qty')->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove fields from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'type',
                'receiver_email',
                'return_fee',
                'return_fee_type',
                'total_refund_amount',
                'vnp_txn_ref',
                'payment_status',
                'status',
                'parent_id'
            ]);
        });

        // Remove return_qty field from order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('return_qty');
        });
    }
};
