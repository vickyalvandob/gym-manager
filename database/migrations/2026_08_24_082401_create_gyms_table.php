<?php

use App\Enums\GymStatus;
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
        Schema::create('gyms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('status', 20)->default(GymStatus::Active->value);
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->char('currency', 3)->default('IDR');
            $table->unsignedTinyInteger('membership_expiry_warning_days')->default(7);
            $table->timestamps();

            $table->index(['status', 'created_at'], 'gyms_status_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
