<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('actor_id')->nullable()->index();
            $table->string('actor_role', 30)->index();
            $table->string('action', 100)->index();
            $table->foreignUuid('document_id')->nullable()->constrained('documents')->index();

            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();

            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('payload');
            } else {
                $table->json('payload');
            }

            $table->timestamps();

            $table->foreign('actor_id')->references('id')->on('users');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE activity_logs ADD CONSTRAINT activity_logs_actor_role_check CHECK (actor_role IN ('admin','warehouse','spv','finance','purchasing','system'))"
            );
            DB::statement(
                "CREATE INDEX activity_logs_payload_gin_idx ON activity_logs USING gin (payload)"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS activity_logs_payload_gin_idx");
        }

        Schema::dropIfExists('activity_logs');
    }
};

