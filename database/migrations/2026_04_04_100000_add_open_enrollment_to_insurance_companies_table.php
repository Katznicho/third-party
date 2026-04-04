<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->boolean('open_enrollment_enabled')->default(false)->after('payment_responsibility_collection');
            $table->unsignedTinyInteger('open_enrollment_min_age')->nullable()->after('open_enrollment_enabled');
            $table->unsignedTinyInteger('open_enrollment_max_age')->nullable()->after('open_enrollment_min_age');
            $table->json('open_enrollment_genders')->nullable()->after('open_enrollment_max_age');
            $table->foreignId('generic_policy_id')->nullable()->constrained('policies')->nullOnDelete()->after('open_enrollment_genders');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generic_policy_id');
            $table->dropColumn([
                'open_enrollment_enabled',
                'open_enrollment_min_age',
                'open_enrollment_max_age',
                'open_enrollment_genders',
            ]);
        });
    }
};
