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
        Schema::table('trainers', function (Blueprint $table) {
            $table->string('trainer_code', 30)->nullable()->after('user_id');
            $table->text('bio')->nullable()->after('specialization');
            $table->date('joined_at')->nullable()->after('status');

            $table->unique(['gym_id', 'trainer_code'], 'trainers_gym_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropUnique('trainers_gym_code_unique');
            $table->dropColumn(['trainer_code', 'bio', 'joined_at']);
        });
    }
};
