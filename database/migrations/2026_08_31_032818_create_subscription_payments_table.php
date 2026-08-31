<?php

use App\Enums\SaasPlanInterval;
use App\Enums\SubscriptionPaymentStatus;
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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('saas_plan_id')->constrained()->restrictOnDelete();
            $table->string('plan_name', 120);
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3);
            $table->string('billing_interval', 20)->default(SaasPlanInterval::Monthly->value);
            $table->string('reference_number', 100);
            $table->string('proof_path');
            $table->string('status', 20)->default(SubscriptionPaymentStatus::Pending->value);
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('period_starts_at')->nullable();
            $table->timestamp('period_ends_at')->nullable();
            $table->timestamps();

            $table->index(
                ['subscription_id', 'status', 'submitted_at'],
                'subscription_payments_subscription_status_index',
            );
            $table->index(['status', 'submitted_at'], 'subscription_payments_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
