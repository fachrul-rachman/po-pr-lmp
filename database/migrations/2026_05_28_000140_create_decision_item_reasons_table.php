<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_item_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_decision_id');
            $table->uuid('document_item_id');
            $table->text('reason');
            $table->timestamps();

            $table->index('document_decision_id');
            $table->index('document_item_id');

            $table->foreign('document_decision_id', 'decision_item_reasons_document_decision_id_fk')
                ->references('id')
                ->on('document_decisions');

            $table->foreign('document_item_id', 'decision_item_reasons_document_item_id_fk')
                ->references('id')
                ->on('document_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_item_reasons');
    }
};
