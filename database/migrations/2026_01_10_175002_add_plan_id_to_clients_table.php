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
            if (!Schema::hasColumn('clients', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('principal_member_id');
            }
        });

        // Add foreign key if it doesn't exist
        $foreignKeys = Schema::getConnection()
            ->select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND CONSTRAINT_NAME = 'clients_plan_id_foreign'");
        
        if (empty($foreignKeys) && Schema::hasColumn('clients', 'plan_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};
