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
        Schema::table('gyms', function (Blueprint $table) {
            $table->unsignedBigInteger('next_trainer_sequence')
                ->default(1)
                ->after('next_invoice_sequence');
            $table->boolean('count_pt_no_show_as_used_session')
                ->default(true)
                ->after('membership_expiry_warning_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn([
                'next_trainer_sequence',
                'count_pt_no_show_as_used_session',
            ]);
        });
    }
};
