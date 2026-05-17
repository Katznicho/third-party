<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connected_company_item_coverages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->unsignedBigInteger('business_connection_id');
            $table->string('service_code', 255);
            $table->decimal('coverage_percent', 5, 2)->default(100.00);
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['insurance_company_id', 'business_connection_id', 'service_code'],
                'cc_item_coverage_unique'
            );

            $table->index(
                ['insurance_company_id', 'business_connection_id'],
                'cc_item_coverage_conn_idx'
            );

            $table->foreign('insurance_company_id', 'ccic_ins_company_fk')
                ->references('id')
                ->on('insurance_companies')
                ->onDelete('cascade');

            $table->foreign('business_connection_id', 'ccic_business_conn_fk')
                ->references('id')
                ->on('business_connections')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_company_item_coverages');
    }
};
