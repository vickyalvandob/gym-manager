<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_membership_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('invoice_number', 40);
            $table->decimal('amount', 14, 2);
            $table->string('method', 30)->nullable();
            $table->string('status', 20)->default(PaymentStatus::Pending->value);
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['gym_id', 'invoice_number'], 'payments_gym_invoice_unique');
            $table->index(['gym_id', 'status', 'created_at'], 'payments_gym_status_created_index');
            $table->index(['gym_id', 'paid_at'], 'payments_gym_paid_at_index');
            $table->index(['gym_id', 'method', 'paid_at'], 'payments_gym_method_paid_index');
            $table->index(['member_id', 'created_at'], 'payments_member_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
