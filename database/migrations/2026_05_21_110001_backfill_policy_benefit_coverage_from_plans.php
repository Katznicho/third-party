<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE policy_benefits pb
            INNER JOIN policies pol ON pol.id = pb.policy_id
            INNER JOIN clients c ON c.id = pol.principal_member_id AND c.plan_id IS NOT NULL
            INNER JOIN plan_service_category psc
                ON psc.plan_id = c.plan_id
                AND psc.service_category_id = pb.service_category_id
            SET pb.coverage_percent = psc.coverage_percent
            WHERE psc.coverage_percent IS NOT NULL
        ');
    }

    public function down(): void
    {
        // Non-reversible data backfill
    }
};
