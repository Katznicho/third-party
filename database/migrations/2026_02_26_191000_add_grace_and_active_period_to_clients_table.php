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
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedSmallInteger('premium_grace_days')
                ->nullable()
                ->after('insurance_payable_percentage')
                ->comment('Override grace period in days for premium payment; null = use company defaults');

            $table->unsignedSmallInteger('active_period_days')
                ->nullable()
                ->after('premium_grace_days')
                ->comment('Number of days client/policy is active after activation; null = use policy dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['premium_grace_days', 'active_period_days']);
        });
    }
};

