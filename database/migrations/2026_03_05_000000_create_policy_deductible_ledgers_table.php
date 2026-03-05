<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_deductible_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurance_company_id');
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('authorization_id')->nullable();
            $table->string('kashtre_invoice_id', 64)->nullable();
            $table->string('external_invoice_number', 64)->nullable();
            $table->string('change_type', 50)->default('invoice'); // e.g. invoice, manual_adjustment
            $table->decimal('deductible_before', 14, 2)->default(0);
            $table->decimal('amount_that_reduces_deductible', 14, 2)->default(0);
            $table->decimal('deductible_after', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['policy_id', 'created_at']);
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->foreign('policy_id')->references('id')->on('policies')->onDelete('cascade');
            $table->foreign('authorization_id')->references('id')->on('insurance_authorizations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_deductible_ledgers');
    }
};

