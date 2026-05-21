<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurer_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->string('name', 64);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['insurance_company_id', 'name'], 'insurer_titles_company_name_unique');
            $table->index('insurance_company_id');
        });

        Schema::create('insurer_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['insurance_company_id', 'name'], 'insurer_qualifications_company_name_unique');
            $table->index('insurance_company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'title_id')) {
                $table->unsignedBigInteger('title_id')->nullable()->after('national_id');
            }
            if (! Schema::hasColumn('users', 'qualification_id')) {
                $table->unsignedBigInteger('qualification_id')->nullable()->after('title_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'qualification_id')) {
                $table->dropColumn('qualification_id');
            }
            if (Schema::hasColumn('users', 'title_id')) {
                $table->dropColumn('title_id');
            }
        });

        Schema::dropIfExists('insurer_qualifications');
        Schema::dropIfExists('insurer_titles');
    }
};
