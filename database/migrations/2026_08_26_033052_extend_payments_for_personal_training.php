<?php

use App\Enums\PaymentType;
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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('member_membership_id')->nullable()->change();
            $table->string('type', 30)
                ->default(PaymentType::Membership->value)
                ->after('member_id');
            $table->foreignId('member_pt_package_id')
                ->nullable()
                ->after('member_membership_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unique('member_pt_package_id', 'payments_member_pt_package_unique');
            $table->index(
                ['gym_id', 'type', 'status', 'paid_at'],
                'payments_gym_type_status_paid_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_gym_type_status_paid_idx');
            $table->dropUnique('payments_member_pt_package_unique');
            $table->dropConstrainedForeignId('member_pt_package_id');
            $table->dropColumn('type');
            $table->foreignId('member_membership_id')->nullable(false)->change();
        });
    }
};
