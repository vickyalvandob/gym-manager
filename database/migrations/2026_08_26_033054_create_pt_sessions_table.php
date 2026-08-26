<?php

use App\Enums\PtSessionStatus;
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
        Schema::create('pt_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_pt_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->constrained()->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('status', 20)->default(PtSessionStatus::Scheduled->value);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('quota_consumed')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['gym_id', 'trainer_id', 'scheduled_at'],
                'pt_sessions_gym_trainer_schedule_idx',
            );
            $table->index(
                ['gym_id', 'member_id', 'scheduled_at'],
                'pt_sessions_gym_member_schedule_idx',
            );
            $table->index(
                ['member_pt_package_id', 'status'],
                'pt_sessions_package_status_idx',
            );
            $table->index(['gym_id', 'status'], 'pt_sessions_gym_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pt_sessions');
    }
};
