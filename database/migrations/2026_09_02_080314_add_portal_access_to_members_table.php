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
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('gym_id')
                ->constrained()
                ->nullOnDelete();
            $table->uuid('portal_qr_token')->nullable()->after('notes')->unique();
            $table->timestamp('portal_enabled_at')->nullable()->after('portal_qr_token');
            $table->unique(['gym_id', 'user_id'], 'members_gym_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_gym_user_unique');
            $table->dropUnique(['portal_qr_token']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['portal_qr_token', 'portal_enabled_at']);
        });
    }
};
