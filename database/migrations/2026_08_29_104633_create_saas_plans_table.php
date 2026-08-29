<?php

use App\Enums\SaasPlanInterval;
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
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2);
            $table->char('currency', 3)->default('IDR');
            $table->string('billing_interval', 20)->default(SaasPlanInterval::Monthly->value);
            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->unsignedInteger('max_members')->nullable();
            $table->unsignedSmallInteger('max_staff')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'id'], 'saas_plans_active_sort_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_plans');
    }
};
