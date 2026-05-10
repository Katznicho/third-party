<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('surname');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('national_id')->nullable()->after('middle_name');
            $table->string('department')->nullable()->after('national_id');
            $table->string('gender', 16)->nullable()->after('department');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('marital_status', 32)->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'surname',
                'first_name',
                'middle_name',
                'national_id',
                'department',
                'gender',
                'birth_date',
                'marital_status',
            ]);
        });
    }
};
