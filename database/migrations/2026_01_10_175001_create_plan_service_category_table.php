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
        Schema::create('plan_service_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('service_category_id');
            $table->decimal('benefit_amount', 15, 2)->nullable(); // Benefit amount for this service category in this plan
            $table->decimal('copay_percentage', 5, 2)->nullable()->default(0); // Co-pay percentage
            $table->decimal('deductible_amount', 15, 2)->nullable()->default(0); // Deductible amount
            $table->integer('waiting_period_days')->default(0); // Waiting period specific to this plan
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'service_category_id']);
        });

        Schema::table('plan_service_category', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_service_category');
    }
};
