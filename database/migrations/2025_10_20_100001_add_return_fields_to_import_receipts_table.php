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
        Schema::table('import_receipts', function (Blueprint $table) {
            $table->string('type', 20)->default('IMPORT')->after('receipt_number'); // IMPORT, RETURN
            $table->foreignId('parent_id')->nullable()->after('employee_id')->constrained('import_receipts')->onDelete('cascade');
            $table->decimal('return_fee', 15, 2)->nullable()->after('total_amount');
            $table->string('return_fee_type', 20)->nullable()->after('return_fee'); // value, percent
            $table->decimal('total_refund_amount', 15, 2)->nullable()->after('return_fee_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_receipts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['type', 'parent_id', 'return_fee', 'return_fee_type', 'total_refund_amount']);
        });
    }
};

