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
        $hasLegacyUnique = Schema::hasIndex(
            'trainer_members',
            'trainer_members_gym_trainer_member_unique',
            'unique',
        );

        Schema::table('trainer_members', function (Blueprint $table) use ($hasLegacyUnique) {
            if ($hasLegacyUnique) {
                $table->dropUnique('trainer_members_gym_trainer_member_unique');
            }

            $table->timestamp('assigned_at')->nullable()->after('member_id');
            $table->timestamp('ended_at')->nullable()->after('assigned_at');
            $table->boolean('is_active')->default(true)->after('ended_at');
            $table->foreignId('ended_by')
                ->nullable()
                ->after('assigned_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable()->after('ended_by');

            $table->index(
                ['gym_id', 'trainer_id', 'is_active'],
                'trainer_members_gym_trainer_active_idx',
            );
            $table->index(
                ['gym_id', 'member_id', 'is_active'],
                'trainer_members_gym_member_active_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainer_members', function (Blueprint $table) {
            $table->dropIndex('trainer_members_gym_trainer_active_idx');
            $table->dropIndex('trainer_members_gym_member_active_idx');
            $table->dropConstrainedForeignId('ended_by');
            $table->dropColumn(['assigned_at', 'ended_at', 'is_active', 'notes']);

            $table->unique(
                ['gym_id', 'trainer_id', 'member_id'],
                'trainer_members_gym_trainer_member_unique',
            );
        });
    }
};
