<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_companies', 'authorization_valid_unit')) {
                $table->string('authorization_valid_unit', 16)
                    ->nullable()
                    ->after('authorization_valid_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_companies', 'authorization_valid_unit')) {
                $table->dropColumn('authorization_valid_unit');
            }
        });
    }
};

