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
     * Subtasks are rows in this same table, one level deep and in the same
     * project (RN-17). The end date cannot fall before the start date (A-06).
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('project_statuses')->restrictOnDelete();
            $table->foreignId('priority_id')->constrained('project_priorities')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'parent_id']);
            $table->index(['project_id', 'status_id', 'position']);
            $table->index('parent_id');
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['task_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('task_task_label', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_label_id')->constrained()->cascadeOnDelete();

            $table->primary(['task_id', 'task_label_id']);
            $table->index('task_label_id');
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
        });

        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('event');
            $table->jsonb('properties')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('task_id');
        });

        DB::statement('alter table tasks add constraint tasks_ends_on_after_starts_on check (starts_on is null or ends_on is null or ends_on >= starts_on)');
        DB::statement('alter table tasks add constraint tasks_parent_is_not_self check (parent_id is null or parent_id <> id)');

        DB::unprepared(<<<'SQL'
            create or replace function tasks_enforce_single_level_subtask()
            returns trigger
            language plpgsql
            as $$
            declare
                parent_project_id bigint;
                parent_parent_id bigint;
            begin
                if new.parent_id is null then
                    return new;
                end if;

                select project_id, parent_id
                into parent_project_id, parent_parent_id
                from tasks
                where id = new.parent_id;

                if not found or parent_parent_id is not null or parent_project_id is distinct from new.project_id then
                    raise exception 'A subtask must belong to a root task in the same project';
                end if;

                if exists (select 1 from tasks where parent_id = new.id) then
                    raise exception 'A task with subtasks cannot become a subtask';
                end if;

                return new;
            end;
            $$;

            create trigger tasks_enforce_single_level_subtask
            before insert or update of parent_id, project_id
            on tasks
            for each row
            execute function tasks_enforce_single_level_subtask();
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

        DB::unprepared('drop function if exists tasks_enforce_single_level_subtask()');
    }
};
