<?php

use App\Enums\MemberPtPackageStatus;
use App\Enums\PaymentStatus;
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
        Schema::create('member_pt_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->constrained()->restrictOnDelete();
            $table->foreignId('pt_package_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('total_sessions');
            $table->unsignedSmallInteger('used_sessions')->default(0);
            $table->date('start_date');
            $table->date('expires_at')->nullable();
            $table->decimal('price', 14, 2);
            $table->string('status', 20)->default(MemberPtPackageStatus::Pending->value);
            $table->string('payment_status', 20)->default(PaymentStatus::Pending->value);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['gym_id', 'member_id'], 'member_pt_packages_gym_member_idx');
            $table->index(['gym_id', 'trainer_id'], 'member_pt_packages_gym_trainer_idx');
            $table->index(['gym_id', 'status'], 'member_pt_packages_gym_status_idx');
            $table->index(['gym_id', 'expires_at'], 'member_pt_packages_gym_expiry_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_pt_packages');
    }
};
