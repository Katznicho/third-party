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
            $table->string('connected_business_name')->nullable()->after('connected_business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_connections', function (Blueprint $table) {
            $table->dropColumn('connected_business_name');
        });
    }
};
