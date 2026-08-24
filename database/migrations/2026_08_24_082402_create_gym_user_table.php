<?php

use App\Enums\GymUserStatus;
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
        Schema::create('gym_user', function (Blueprint $table) {
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('status', 20)->default(GymUserStatus::Active->value);
            $table->timestamps();

            $table->unique(['gym_id', 'user_id']);
            $table->index(['gym_id', 'status', 'role'], 'gym_user_gym_status_role_index');
            $table->index(['user_id', 'status'], 'gym_user_user_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_user');
    }
};
