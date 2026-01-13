<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('authorization_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_authorization_id')->constrained()->onDelete('cascade');
            
            // Item Details
            $table->string('item_code')->nullable(); // Service/Procedure code
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->string('item_category')->nullable(); // Procedure, Medication, Lab Test, etc.
            
            // Quantity and Pricing
            $table->integer('quantity')->default(1);
            $table->string('unit')->nullable(); // Each, Package, etc.
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_amount', 15, 2);
            
            // Authorization Status (can be approved/rejected individually)
            $table->enum('authorization_status', [
                'pending',
                'approved',
                'rejected',
                'partially_approved'
            ])->default('pending');
            
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('authorization_notes')->nullable();
            
            // Service Provider
            $table->string('provider_name')->nullable();
            $table->string('provider_code')->nullable();
            
            // Visit Association
            $table->string('visit_reference')->nullable(); // Link to specific visit
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorization_items');
    }
};
