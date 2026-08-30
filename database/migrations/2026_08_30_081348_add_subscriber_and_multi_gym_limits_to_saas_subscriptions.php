<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_gyms')->nullable()->after('trial_days');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('subscriber_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->restrictOnDelete();
        });

        Schema::table('gyms', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('subscriptions')
            ->select(['id', 'gym_id'])
            ->orderBy('id')
            ->cursor()
            ->each(function (object $subscription): void {
                $subscriberId = DB::table('gym_user')
                    ->where('gym_id', $subscription->gym_id)
                    ->where('role', 'owner')
                    ->oldest('created_at')
                    ->value('user_id');

                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['subscriber_id' => $subscriberId]);
                DB::table('gyms')
                    ->where('id', $subscription->gym_id)
                    ->update(['subscription_id' => $subscription->id]);
            });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscriber_id')->nullable(false)->change();
            $table->unique('subscriber_id');
            $table->dropForeign(['gym_id']);
            $table->dropUnique(['gym_id']);
            $table->dropColumn('gym_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('gym_id')->nullable()->after('subscriber_id');
        });

        DB::table('subscriptions')
            ->select(['id'])
            ->orderBy('id')
            ->cursor()
            ->each(function (object $subscription): void {
                $primaryGymId = DB::table('gyms')
                    ->where('subscription_id', $subscription->id)
                    ->oldest('id')
                    ->value('id');

                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['gym_id' => $primaryGymId]);
            });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('gym_id')->nullable(false)->change();
            $table->unique('gym_id');
            $table->foreign('gym_id')->references('id')->on('gyms')->cascadeOnDelete();
        });

        if (Schema::hasColumn('gyms', 'subscription_id')) {
            Schema::table('gyms', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subscription_id');
            });
        }

        if (Schema::hasColumn('subscriptions', 'subscriber_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropForeign(['subscriber_id']);
                $table->dropUnique(['subscriber_id']);
                $table->dropColumn('subscriber_id');
            });
        }

        if (Schema::hasColumn('saas_plans', 'max_gyms')) {
            Schema::table('saas_plans', function (Blueprint $table) {
                $table->dropColumn('max_gyms');
            });
        }
    }
};
