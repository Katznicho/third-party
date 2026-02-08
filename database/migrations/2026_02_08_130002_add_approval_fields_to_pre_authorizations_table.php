<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->string('approval_id')->unique()->nullable()->after('authorization_number');
            $table->unsignedBigInteger('triggered_by_trigger_id')->nullable()->after('approved_by');
            $table->enum('trigger_reason', ['high_cost', 'special_procedure', 'keyword_match', 'manual', 'coverage_check'])->nullable()->after('triggered_by_trigger_id');
            
            $table->foreign('triggered_by_trigger_id')->references('id')->on('pre_authorization_triggers')->onDelete('set null');
            $table->index('approval_id');
        });
    }

    public function down(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->dropForeign(['triggered_by_trigger_id']);
            $table->dropIndex(['approval_id']);
            $table->dropColumn(['approval_id', 'triggered_by_trigger_id', 'trigger_reason']);
        });
    }
};
