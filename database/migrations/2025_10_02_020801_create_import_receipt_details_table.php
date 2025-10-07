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
        Schema::create('import_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0);

            $table->foreignId('import_receipt_id')
                ->constrained('import_receipts')
                ->onDelete('cascade');

            $table->foreignId('supply_id')
                ->constrained('supplies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_receipt_details');
    }
};
