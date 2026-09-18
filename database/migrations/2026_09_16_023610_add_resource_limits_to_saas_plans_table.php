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
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_trainers')->nullable();
            $table->unsignedInteger('max_membership_plans')->nullable();
            $table->unsignedInteger('max_pt_packages')->nullable();
            $table->unsignedSmallInteger('trial_days')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->dropColumn(['max_trainers', 'max_membership_plans', 'max_pt_packages']);
            $table->unsignedSmallInteger('trial_days')->default(14)->change();
        });
    }
};
