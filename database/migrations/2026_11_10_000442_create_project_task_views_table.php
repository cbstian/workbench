<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The view and filters each user last left active on a project (RN-11).
     * Filters are a UI preference read and written whole, so they stay in
     * JSON; stale ids inside are ignored when read.
     */
    public function up(): void
    {
        Schema::create('project_task_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('view', 16);
            $table->jsonb('filters')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();

            $table->unique(['user_id', 'project_id']);
            $table->index('project_id');
        });

        DB::statement("alter table project_task_views add constraint project_task_views_view_valid check (view in ('kanban', 'list', 'calendar'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_views');
    }
};
