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
        DB::table('saas_plans')->update(['trial_days' => 0]);
        DB::table('saas_plans')->where('slug', 'free')->update([
            'max_trainers' => 5,
            'max_membership_plans' => 5,
            'max_pt_packages' => 5,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Plan configuration is retained; reverting it could overwrite administrator changes.
    }
};
