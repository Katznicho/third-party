<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE policies MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'expired', 'cancelled', 'pending_payment') DEFAULT 'active'");
        } else {
            Schema::table('policies', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally revert - set any pending_payment to inactive first
        DB::table('policies')->where('status', 'pending_payment')->update(['status' => 'inactive']);
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE policies MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'expired', 'cancelled') DEFAULT 'active'");
        }
    }
};
