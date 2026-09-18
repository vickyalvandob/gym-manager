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
        Schema::table('member_memberships', function (Blueprint $table): void {
            $table->timestamp('activated_at')->nullable();
        });
        Schema::table('member_pt_packages', function (Blueprint $table): void {
            $table->string('package_name', 120)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_memberships', fn (Blueprint $table) => $table->dropColumn('activated_at'));
        Schema::table('member_pt_packages', fn (Blueprint $table) => $table->dropColumn('package_name'));
    }
};
