<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rejected_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_authorization_id')
                ->constrained('insurance_authorizations')
                ->cascadeOnDelete();

            $table->string('item_name');
            $table->string('item_code')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('reason_scope')->nullable();

            $table->timestamps();

            $table->index(['insurance_authorization_id'], 'rejected_items_auth_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rejected_items');
    }
};

