<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->index();

            $table->string('decision_type', 50)->index();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->text('reason')->nullable();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('actor_role', 30)->index();

            $table->timestamps();

            $table->foreign('actor_id')->references('id')->on('users');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE document_decisions ADD CONSTRAINT document_decisions_decision_type_check CHECK (decision_type IN ('warehouse_submit','warehouse_resubmit','spv_approve','spv_reject','finance_close','finance_reject','admin_override','accurate_refresh','system_status_change'))"
            );
            DB::statement(
                "ALTER TABLE document_decisions ADD CONSTRAINT document_decisions_actor_role_check CHECK (actor_role IN ('admin','warehouse','spv','finance','purchasing','system'))"
            );
            DB::statement(
                "ALTER TABLE document_decisions ADD CONSTRAINT document_decisions_to_status_check CHECK (to_status IN ('warehouse_submitted','spv_approved','spv_rejected','finance_rejected','finance_closed'))"
            );
            DB::statement(
                "ALTER TABLE document_decisions ADD CONSTRAINT document_decisions_from_status_check CHECK (from_status IS NULL OR from_status IN ('warehouse_submitted','spv_approved','spv_rejected','finance_rejected','finance_closed'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_decisions');
    }
};

