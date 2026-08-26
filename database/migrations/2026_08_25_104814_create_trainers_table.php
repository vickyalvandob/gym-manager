<?php

use App\Enums\TrainerStatus;
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
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('specialization', 160)->nullable();
            $table->string('status', 20)->default(TrainerStatus::Active->value);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['gym_id', 'email'], 'trainers_gym_email_unique');
            $table->unique(['gym_id', 'user_id'], 'trainers_gym_user_unique');
            $table->index(['gym_id', 'status', 'name'], 'trainers_gym_status_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
