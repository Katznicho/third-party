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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['principal', 'dependent'])->default('principal');
            $table->unsignedBigInteger('principal_member_id')->nullable();
            
            // Personal Details
            $table->string('surname')->nullable();
            $table->string('first_name');
            $table->string('other_names')->nullable();
            $table->string('title')->nullable();
            $table->string('id_passport_no')->unique();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('tin')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
            $table->string('height')->nullable(); // ft & inches
            $table->string('weight')->nullable(); // Kgs
            
            // Employment
            $table->string('employer_name')->nullable();
            $table->string('occupation')->nullable();
            $table->string('nationality')->nullable();
            
            // Contact Details
            $table->text('home_physical_address')->nullable();
            $table->text('office_physical_address')->nullable();
            $table->string('home_telephone')->nullable();
            $table->string('office_telephone')->nullable();
            $table->string('cell_phone')->nullable();
            $table->string('whatsapp_line')->nullable();
            $table->string('email')->nullable();
            
            // Relationship (for dependents)
            $table->string('relation_to_principal')->nullable(); // For dependents
            
            // Next of Kin Details
            $table->string('next_of_kin_surname')->nullable();
            $table->string('next_of_kin_first_name')->nullable();
            $table->string('next_of_kin_other_names')->nullable();
            $table->string('next_of_kin_title')->nullable();
            $table->string('next_of_kin_relation')->nullable();
            $table->string('next_of_kin_id_passport_no')->nullable();
            $table->string('next_of_kin_cell_phone')->nullable();
            $table->string('next_of_kin_email')->nullable();
            $table->text('next_of_kin_post_address')->nullable();
            $table->text('next_of_kin_physical_address')->nullable();
            
            // Medical History (JSON)
            $table->json('medical_history')->nullable();
            $table->json('regular_medications')->nullable();
            
            $table->boolean('has_deductible')->default(false);
            $table->decimal('deductible_amount', 15, 2)->default(100000); // UGX 100,000 default
            $table->boolean('telemedicine_only')->default(false);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Add foreign key constraint for self-referencing principal_member_id
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('principal_member_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
