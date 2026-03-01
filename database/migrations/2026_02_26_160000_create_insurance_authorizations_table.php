<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kashtre_invoice_id')->nullable()->index();
            $table->string('external_invoice_number')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->decimal('client_total', 14, 2)->default(0);
            $table->decimal('insurance_total', 14, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->string('status', 32)->default('pending'); // pending, completed, failed
            $table->string('confirmation_code')->nullable();
            $table->string('authorization_reference', 64)->unique();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_authorizations');
    }
};
