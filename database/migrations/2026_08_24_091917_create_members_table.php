<?php

use App\Enums\MemberStatus;
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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('member_number', 30);
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->string('emergency_contact', 120)->nullable();
            $table->string('status', 20)->default(MemberStatus::Active->value);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['gym_id', 'member_number'], 'members_gym_number_unique');
            $table->index(['gym_id', 'status', 'created_at'], 'members_gym_status_created_index');
            $table->index(['gym_id', 'name'], 'members_gym_name_index');
            $table->index(['gym_id', 'phone'], 'members_gym_phone_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
