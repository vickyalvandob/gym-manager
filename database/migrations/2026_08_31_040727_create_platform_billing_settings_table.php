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
        Schema::create('platform_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 120)->nullable();
            $table->string('account_name', 120)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->text('instructions')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_billing_settings');
    }
};
