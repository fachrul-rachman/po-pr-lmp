<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_item_id')->constrained('document_items')->index();
            $table->uuid('uploaded_by')->index();

            $table->string('disk', 50);
            $table->text('path');
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');

            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE item_photos ADD CONSTRAINT item_photos_disk_check CHECK (disk = 'r2')"
            );
            DB::statement(
                "ALTER TABLE item_photos ADD CONSTRAINT item_photos_path_check CHECK (length(path) > 0)"
            );
            DB::statement(
                "ALTER TABLE item_photos ADD CONSTRAINT item_photos_size_bytes_check CHECK (size_bytes > 0)"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_photos');
    }
};

