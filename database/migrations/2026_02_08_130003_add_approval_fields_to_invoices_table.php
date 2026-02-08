<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('pre_authorization_id')->nullable()->after('policy_id');
            $table->string('approval_id')->nullable()->after('pre_authorization_id');
            $table->enum('coverage_decision', ['approved', 'rejected', 'flagged_for_review', 'pending'])->nullable()->after('approval_id');
            $table->text('coverage_decision_notes')->nullable()->after('coverage_decision');
            
            $table->foreign('pre_authorization_id')->references('id')->on('pre_authorizations')->onDelete('set null');
            $table->index('approval_id');
            $table->index('coverage_decision');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['pre_authorization_id']);
            $table->dropIndex(['approval_id']);
            $table->dropIndex(['coverage_decision']);
            $table->dropColumn(['pre_authorization_id', 'approval_id', 'coverage_decision', 'coverage_decision_notes']);
        });
    }
};
