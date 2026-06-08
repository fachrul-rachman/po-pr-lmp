<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('accurate_company', 20)->nullable()->index();
            $table->string('accurate_trans_date', 50)->nullable()->index();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE documents ADD CONSTRAINT documents_accurate_company_check CHECK (accurate_company IS NULL OR accurate_company IN ('kpus','ahl'))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_accurate_company_check');
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['accurate_company', 'accurate_trans_date']);
        });
    }
};

