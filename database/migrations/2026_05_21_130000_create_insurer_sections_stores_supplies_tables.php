<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurer_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['insurance_company_id', 'name'], 'insurer_sections_company_name_unique');
            $table->index('insurance_company_id');
        });

        Schema::create('insurer_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['insurance_company_id', 'name'], 'insurer_stores_company_name_unique');
            $table->index('insurance_company_id');
        });

        Schema::create('insurer_supplies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['insurance_company_id', 'name'], 'insurer_supplies_company_name_unique');
            $table->index('insurance_company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('qualification_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'section_id')) {
                $table->dropColumn('section_id');
            }
        });

        Schema::dropIfExists('insurer_supplies');
        Schema::dropIfExists('insurer_stores');
        Schema::dropIfExists('insurer_sections');
    }
};
