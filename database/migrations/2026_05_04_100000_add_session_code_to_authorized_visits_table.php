<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authorized_visits', function (Blueprint $table) {
            $table->string('session_code', 64)->nullable()->unique()->after('visit_id');
            $table->dateTime('session_expires_at')->nullable()->after('expiry_at')
                ->comment('Insurer visit-authorization window from settings (visit date + N days)');
            $table->index('session_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('authorized_visits', function (Blueprint $table) {
            $table->dropIndex(['session_expires_at']);
            $table->dropUnique(['session_code']);
            $table->dropColumn(['session_code', 'session_expires_at']);
        });
    }
};
