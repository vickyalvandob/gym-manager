<?php

use App\Enums\MembershipDurationUnit;
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
        Schema::create('member_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('renewed_from_id')
                ->nullable()
                ->constrained('member_memberships')
                ->nullOnDelete();
            $table->string('plan_name', 120);
            $table->unsignedSmallInteger('duration');
            $table->string('duration_unit', 20)->default(MembershipDurationUnit::Month->value);
            $table->decimal('price', 14, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['member_id', 'start_date'], 'member_memberships_member_start_unique');
            $table->index(['gym_id', 'end_date'], 'member_memberships_gym_end_index');
            $table->index(
                ['member_id', 'start_date', 'end_date'],
                'member_memberships_member_period_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_memberships');
    }
};
