<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_local_exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->unsignedBigInteger('client_id');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['insurance_company_id', 'client_id'], 'client_local_exclusions_idx');

            $table->foreign('insurance_company_id', 'cle_ins_company_fk')
                ->references('id')->on('insurance_companies')
                ->onDelete('cascade');

            $table->foreign('client_id', 'cle_client_fk')
                ->references('id')->on('clients')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_local_exclusions');
    }
};

