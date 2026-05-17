<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->decimal('kashtre_service_charge', 15, 2)->default(0)->after('stamp_duty');
            $table->unsignedBigInteger('kashtre_connected_business_id')->nullable()->after('kashtre_service_charge');
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn(['kashtre_service_charge', 'kashtre_connected_business_id']);
        });
    }
};
