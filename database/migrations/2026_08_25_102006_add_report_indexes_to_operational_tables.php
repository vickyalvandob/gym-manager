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
        Schema::table('member_memberships', function (Blueprint $table) {
            $table->index(
                ['gym_id', 'start_date', 'end_date', 'member_id'],
                'memberships_gym_period_member_idx',
            );
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(
                ['gym_id', 'status', 'paid_at'],
                'payments_gym_status_paid_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_memberships', function (Blueprint $table) {
            $table->dropIndex('memberships_gym_period_member_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_gym_status_paid_idx');
        });
    }
};
