<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('accurate_id', 100)->index();
            $table->string('document_number', 100)->unique();
            $table->string('document_type', 10)->index();
            $table->string('status', 50)->nullable()->index();

            $table->text('tujuan_pembelian')->nullable();
            $table->text('dikirim_ke')->nullable();
            $table->string('department', 255)->nullable();
            $table->string('dibuat_oleh', 255)->nullable();
            $table->string('diminta_oleh', 255)->nullable();

            $table->timestamp('accurate_synced_at');

            $table->timestamp('warehouse_submitted_at')->nullable();
            $table->uuid('warehouse_submitted_by')->nullable()->index();

            $table->timestamp('spv_processed_at')->nullable();
            $table->uuid('spv_processed_by')->nullable()->index();

            $table->timestamp('finance_processed_at')->nullable();
            $table->uuid('finance_processed_by')->nullable()->index();

            $table->timestamp('admin_overridden_at')->nullable();
            $table->uuid('admin_overridden_by')->nullable()->index();

            $table->timestamps();

            $table->foreign('warehouse_submitted_by')->references('id')->on('users');
            $table->foreign('spv_processed_by')->references('id')->on('users');
            $table->foreign('finance_processed_by')->references('id')->on('users');
            $table->foreign('admin_overridden_by')->references('id')->on('users');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE documents ADD CONSTRAINT documents_document_type_check CHECK (document_type IN ('po','pr'))"
            );
            DB::statement(
                "ALTER TABLE documents ADD CONSTRAINT documents_status_check CHECK (status IS NULL OR status IN ('warehouse_submitted','spv_approved','spv_rejected','finance_rejected','finance_closed'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

