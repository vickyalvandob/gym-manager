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
        Schema::create('trainer_members', function (Blueprint $table) {
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['gym_id', 'trainer_id', 'member_id'],
                'trainer_members_gym_trainer_member_unique',
            );
            $table->index(['gym_id', 'member_id'], 'trainer_members_gym_member_index');
            $table->index(['trainer_id', 'created_at'], 'trainer_members_trainer_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_members');
    }
};
