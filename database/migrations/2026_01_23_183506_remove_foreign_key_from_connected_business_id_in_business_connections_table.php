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
        Schema::table('business_connections', function (Blueprint $table) {
            // Drop the foreign key constraint on connected_business_id
            // Kashtre business IDs don't need to exist in insurance_companies table
            $table->dropForeign(['connected_business_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_connections', function (Blueprint $table) {
            // Re-add the foreign key constraint if rolling back
            $table->foreign('connected_business_id')
                ->references('id')
                ->on('insurance_companies')
                ->onDelete('cascade');
        });
    }
};
