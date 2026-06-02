<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->index();

            $table->string('accurate_item_id', 100)->index();
            $table->text('nama_barang');
            $table->text('keterangan')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->string('satuan', 100);

            $table->string('match_status', 30)->nullable()->index();
            $table->text('warehouse_reason')->nullable();

            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE document_items ADD CONSTRAINT document_items_quantity_check CHECK (quantity >= 0)"
            );
            DB::statement(
                "ALTER TABLE document_items ADD CONSTRAINT document_items_match_status_check CHECK (match_status IS NULL OR match_status IN ('sesuai','tidak_sesuai'))"
            );
            DB::statement(
                "ALTER TABLE document_items ADD CONSTRAINT document_items_warehouse_reason_required_check CHECK (match_status IS DISTINCT FROM 'tidak_sesuai' OR (warehouse_reason IS NOT NULL AND length(btrim(warehouse_reason)) > 0))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};

