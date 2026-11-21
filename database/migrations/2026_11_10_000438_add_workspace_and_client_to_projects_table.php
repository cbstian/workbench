<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * workspace_id is required and has no backfill: refresh a database that
     * already has project rows. client_id stays nullable (RN-02). The composite
     * foreign key is skipped by PostgreSQL while client_id is null.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->nullable();
            $table->unique(['id', 'workspace_id']);
            $table->index('workspace_id');
            $table->index('client_id');

            $table->foreign(['client_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])
                ->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id', 'workspace_id']);
            $table->dropForeign(['workspace_id']);
            $table->dropUnique(['id', 'workspace_id']);
            $table->dropIndex(['workspace_id']);
            $table->dropIndex(['client_id']);
            $table->dropColumn(['workspace_id', 'client_id']);
        });
    }
};
