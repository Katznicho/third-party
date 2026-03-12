<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // In case the table was partially created before the foreign key failed
        Schema::dropIfExists('connected_company_service_exclusions');

        Schema::create('connected_company_service_exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->unsignedBigInteger('business_connection_id');
            $table->enum('service_category', [
                'dental',
                'optical',
                'outpatient',
                'inpatient',
                'maternity',
                'funeral',
            ])->nullable();
            $table->string('service_code')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(
                ['insurance_company_id', 'business_connection_id', 'service_category', 'service_code'],
                'connected_company_exclusions_idx'
            );

            $table->foreign('insurance_company_id', 'ccse_ins_company_fk')
                ->references('id')
                ->on('insurance_companies')
                ->onDelete('cascade');

            $table->foreign('business_connection_id', 'ccse_business_conn_fk')
                ->references('id')
                ->on('business_connections')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_company_service_exclusions');
    }
};

