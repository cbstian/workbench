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
     * Project priority, due date and pause feed the workspace board (E11).
     * Notes live in board_entries; hiding a row is per user (board_project_hides).
     * The row order (projects.board_position) is shared by the workspace.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('priority', 16)->default('none');
            $table->date('due_on')->nullable();
            $table->timestampTz('paused_at')->nullable();
            $table->foreignId('paused_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->bigInteger('board_position')->default(0);
        });

        DB::statement("alter table projects add constraint projects_priority_check check (priority in ('none', 'low', 'medium', 'high', 'urgent'))");
        DB::statement('update projects set board_position = id * 1024');
        DB::statement('create index projects_workspace_board_position_index on projects (workspace_id, board_position) where deleted_at is null');

        Schema::create('board_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->date('occurs_on');
            $table->string('body', 80);
            $table->string('status', 16);
            $table->bigInteger('position');
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['project_id', 'occurs_on']);
        });

        Schema::table('board_entries', function (Blueprint $table) {
            $table->foreign(['project_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('projects');
            $table->foreign(['task_id', 'project_id'])
                ->references(['id', 'project_id'])->on('tasks');
        });

        DB::statement("alter table board_entries add constraint board_entries_status_check check (status in ('planned', 'done'))");
        DB::statement('alter table board_entries add constraint board_entries_body_not_blank check (length(btrim(body)) > 0)');
        DB::statement('alter table board_entries add constraint board_entries_done_has_completed_at check ((status = \'done\') = (completed_at is not null))');

        Schema::create('board_project_hides', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['user_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_project_hides');
        Schema::dropIfExists('board_entries');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paused_by_id');
            $table->dropColumn(['priority', 'due_on', 'paused_at', 'board_position']);
        });
    }
};
