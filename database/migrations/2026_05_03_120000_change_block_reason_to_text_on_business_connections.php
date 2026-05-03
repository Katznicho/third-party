<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reasons can be long; controller allows max 1000 chars. Avoids doctrine/dbal (no ->change()).
     */
    public function up(): void
    {
        if (! Schema::hasTable('business_connections') || ! Schema::hasColumn('business_connections', 'block_reason')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `business_connections` MODIFY `block_reason` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('business_connections') || ! Schema::hasColumn('business_connections', 'block_reason')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `business_connections` MODIFY `block_reason` VARCHAR(255) NULL');
        }
    }
};
