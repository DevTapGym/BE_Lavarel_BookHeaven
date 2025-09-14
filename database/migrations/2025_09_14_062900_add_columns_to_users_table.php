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
        Schema::table('users', function (Blueprint $table) {
            $table->text('current_token')->nullable();
            $table->text('refresh_token', 255)->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('activation_code')->nullable();
            $table->timestamp('activation_expires_at')->nullable();
            $table->timestamp('last_activation_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'current_token',
                'refresh_token',
                'is_active',
                'activation_code',
                'activation_expires_at',
                'last_activation_sent_at'
            ]);
        });
    }
};
