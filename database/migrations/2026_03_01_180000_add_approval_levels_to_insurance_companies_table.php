<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'invoice_authorization_levels')) {
                $table->unsignedTinyInteger('invoice_authorization_levels')->default(1)->after('manual_review_threshold_amount');
            }
            if (!Schema::hasColumn('insurance_companies', 'invoice_clearing_trigger')) {
                $table->string('invoice_clearing_trigger')->nullable()->after('invoice_authorization_levels');
            }
            if (!Schema::hasColumn('insurance_companies', 'authorization_valid_days')) {
                $table->unsignedSmallInteger('authorization_valid_days')->nullable()->after('invoice_clearing_trigger');
            }
            if (!Schema::hasColumn('insurance_companies', 'require_reauthorize_if_edited')) {
                $table->boolean('require_reauthorize_if_edited')->default(false)->after('authorization_valid_days');
            }
        });

        if (!Schema::hasTable('pre_authorization_approvers')) {
            Schema::create('pre_authorization_approvers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('level');
                $table->timestamps();

                $table->unique(['insurance_company_id', 'user_id', 'level'], 'pa_approvers_company_user_level_unique');
            });
        }

        if (!Schema::hasTable('pre_authorization_approvals')) {
            Schema::create('pre_authorization_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pre_authorization_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('level');
                $table->foreignId('user_id')->constrained();
                $table->string('action');
                $table->text('notes')->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_authorization_approvals');
        Schema::dropIfExists('pre_authorization_approvers');

        Schema::table('insurance_companies', function (Blueprint $table) {
            $columns = ['invoice_authorization_levels', 'invoice_clearing_trigger', 'authorization_valid_days', 'require_reauthorize_if_edited'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('insurance_companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
