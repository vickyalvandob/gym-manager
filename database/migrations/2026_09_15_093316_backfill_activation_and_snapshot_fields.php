<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('member_memberships')->whereNull('activated_at')->update([
            'activated_at' => DB::table('payments')->select('paid_at')
                ->whereColumn('payments.member_membership_id', 'member_memberships.id')
                ->whereColumn('payments.gym_id', 'member_memberships.gym_id')
                ->where('status', 'paid')->limit(1),
        ]);
        DB::table('member_pt_packages')->whereNull('package_name')->update([
            'package_name' => DB::table('pt_packages')->select('name')
                ->whereColumn('pt_packages.id', 'member_pt_packages.pt_package_id')->limit(1),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** Historical snapshots must not be erased on rollback. The preceding schema migration removes these columns. */
    }
};
