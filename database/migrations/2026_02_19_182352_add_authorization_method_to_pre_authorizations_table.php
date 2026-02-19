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
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->enum('authorization_method', ['automatic', 'manual'])->nullable()->after('status');
            $table->foreignId('authorization_rule_id')->nullable()->after('authorization_method')->constrained('authorization_rules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->dropForeign(['authorization_rule_id']);
            $table->dropColumn(['authorization_method', 'authorization_rule_id']);
        });
    }
};
