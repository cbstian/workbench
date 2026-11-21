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
     * Tasks and one level of subtasks share a table (RN-17). Composite foreign
     * keys keep the status, priority, parent, labels and assignees inside the
     * task's project; assignees must be on the project team, which never holds
     * client users (RN-18). History rows are append-only (RN-08).
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('priority_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->bigInteger('position')->default(0);
            $table->timestampTz('completed_at')->nullable();
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'project_id']);
            $table->unique(['id', 'workspace_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign(['project_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('projects');
            $table->foreign(['parent_id', 'project_id'])
                ->references(['id', 'project_id'])->on('tasks');
            $table->foreign(['status_id', 'project_id'])
                ->references(['id', 'project_id'])->on('project_statuses');
            $table->foreign(['priority_id', 'project_id'])
                ->references(['id', 'project_id'])->on('project_priorities');
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['task_id', 'user_id']);
            $table->index(['user_id', 'task_id']);

            $table->foreign(['task_id', 'project_id'])
                ->references(['id', 'project_id'])->on('tasks')
                ->cascadeOnDelete();
            $table->foreign(['project_id', 'user_id'])
                ->references(['project_id', 'user_id'])->on('project_user')
                ->cascadeOnDelete();
        });

        Schema::create('task_task_label', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('task_label_id');

            $table->primary(['task_id', 'task_label_id']);
            $table->index(['task_label_id', 'task_id']);

            $table->foreign(['task_id', 'project_id'])
                ->references(['id', 'project_id'])->on('tasks')
                ->cascadeOnDelete();
            $table->foreign(['task_label_id', 'project_id'])
                ->references(['id', 'project_id'])->on('task_labels')
                ->cascadeOnDelete();
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('task_id');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestampTz('edited_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->foreign(['task_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('tasks');
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('task_id');
            $table->foreignId('uploaded_by_id')->constrained('users')->restrictOnDelete();
            $table->string('disk', 32);
            $table->string('path', 1024)->unique();
            $table->string('original_name');
            $table->string('mime_type', 127)->nullable();
            $table->bigInteger('size_bytes');
            $table->char('checksum_sha256', 64)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->foreign(['task_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('tasks');
        });

        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('task_id');
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event', 48);
            $table->jsonb('changes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign(['task_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('tasks');
        });

        DB::statement(<<<'SQL'
            alter table tasks add column search_vector tsvector
            generated always as (
                setweight(immutable_tsvector(name), 'A') || setweight(immutable_tsvector(description), 'B')
            ) stored
            SQL);

        DB::statement('alter table tasks add constraint tasks_ends_on_after_starts_on check (starts_on is null or ends_on is null or ends_on >= starts_on)');
        DB::statement('alter table tasks add constraint tasks_parent_is_not_self check (parent_id is null or parent_id <> id)');
        DB::statement('alter table task_attachments add constraint task_attachments_size_bytes_positive check (size_bytes >= 0)');

        DB::statement('create index tasks_kanban_index on tasks (project_id, status_id, position) where deleted_at is null and parent_id is null');
        DB::statement('create index tasks_list_index on tasks (project_id, position, id) where deleted_at is null');
        DB::statement('create index tasks_subtasks_index on tasks (parent_id, position) where deleted_at is null and parent_id is not null');
        DB::statement("create index tasks_calendar_index on tasks using gist (project_id, daterange(starts_on, ends_on, '[]')) where deleted_at is null and (starts_on is not null or ends_on is not null)");
        DB::statement('create index tasks_search_vector_index on tasks using gin (search_vector)');
        DB::statement('create index tasks_name_trgm_index on tasks using gin (name gin_trgm_ops) where deleted_at is null');
        DB::statement('create index tasks_trash_index on tasks (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index task_comments_task_id_created_at_index on task_comments (task_id, created_at) where deleted_at is null');
        DB::statement('create index task_comments_trash_index on task_comments (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index task_attachments_task_id_index on task_attachments (task_id) where deleted_at is null');
        DB::statement('create index task_attachments_trash_index on task_attachments (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index task_activities_task_id_created_at_index on task_activities (task_id, created_at desc)');
        DB::statement('create index task_activities_created_at_brin on task_activities using brin (created_at)');

        DB::unprepared(<<<'SQL'
            create or replace function tasks_enforce_single_level_subtask()
            returns trigger
            language plpgsql
            as $$
            begin
                if new.parent_id is null then
                    return new;
                end if;

                if exists (select 1 from tasks where id = new.parent_id and parent_id is not null) then
                    raise exception 'A subtask cannot have subtasks (RN-17)'
                        using errcode = 'check_violation';
                end if;

                if exists (select 1 from tasks where parent_id = new.id) then
                    raise exception 'A task with subtasks cannot become a subtask (RN-17)'
                        using errcode = 'check_violation';
                end if;

                return new;
            end;
            $$;

            create trigger tasks_enforce_single_level_subtask
            before insert or update of parent_id
            on tasks
            for each row
            execute function tasks_enforce_single_level_subtask();

            create or replace function tasks_apply_workflow()
            returns trigger
            language plpgsql
            as $$
            declare
                status_category text;
                status_deleted_at timestamptz;
            begin
                select category, deleted_at
                into status_category, status_deleted_at
                from project_statuses
                where id = new.status_id;

                if status_deleted_at is not null then
                    raise exception 'A task cannot use a deleted status (RN-07)'
                        using errcode = 'check_violation';
                end if;

                if exists (select 1 from project_priorities where id = new.priority_id and deleted_at is not null) then
                    raise exception 'A task cannot use a deleted priority'
                        using errcode = 'check_violation';
                end if;

                if status_category in ('completed', 'cancelled') then
                    if tg_op = 'INSERT' or old.completed_at is null then
                        new.completed_at := coalesce(new.completed_at, now());
                    end if;
                else
                    new.completed_at := null;
                end if;

                return new;
            end;
            $$;

            create trigger tasks_apply_workflow
            before insert or update of status_id, priority_id
            on tasks
            for each row
            execute function tasks_apply_workflow();

            create trigger task_activities_append_only
            before update or delete on task_activities
            for each row
            execute function forbid_mutation();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_activities');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_task_label');
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');

        DB::statement('drop function if exists tasks_apply_workflow()');
        DB::statement('drop function if exists tasks_enforce_single_level_subtask()');
    }
};
