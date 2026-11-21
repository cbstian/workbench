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
     * Statuses, priorities and task labels are configured per project (RN-06,
     * RN-16). Each status declares a fixed category so the application knows
     * which tasks are closed regardless of the name. At most one active
     * initial status and priority per project (RN-22), and the initial one
     * cannot be trashed until another takes its place (RN-27).
     */
    public function up(): void
    {
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 64);
            $table->string('category', 16);
            $table->char('color', 7)->nullable();
            $table->bigInteger('position');
            $table->boolean('is_initial')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'project_id']);
        });

        Schema::create('project_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 64);
            $table->char('color', 7)->nullable();
            $table->bigInteger('position');
            $table->boolean('is_initial')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'project_id']);
        });

        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 64);
            $table->char('color', 7)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'project_id']);
        });

        DB::statement("alter table project_statuses add constraint project_statuses_category_valid check (category in ('backlog', 'unstarted', 'started', 'completed', 'cancelled'))");

        foreach (['project_statuses', 'project_priorities', 'task_labels'] as $tableName) {
            DB::statement("alter table {$tableName} add constraint {$tableName}_color_hex check (color ~ '^#[0-9a-fA-F]{6}$')");
            DB::statement("create unique index {$tableName}_project_id_name_active_unique on {$tableName} (project_id, lower(name)) where deleted_at is null");
            DB::statement("create index {$tableName}_trash_index on {$tableName} (project_id, deleted_at) where deleted_at is not null");
        }

        foreach (['project_statuses', 'project_priorities'] as $tableName) {
            DB::statement("create index {$tableName}_project_id_position_index on {$tableName} (project_id, position) where deleted_at is null");
            DB::statement("create unique index {$tableName}_one_initial_per_project on {$tableName} (project_id) where is_initial and deleted_at is null");
        }

        DB::unprepared(<<<'SQL'
            create or replace function protect_initial_workflow_value()
            returns trigger
            language plpgsql
            as $$
            begin
                if new.is_initial and new.deleted_at is not null then
                    raise exception 'The initial value of a project cannot be deleted (RN-27)'
                        using errcode = 'check_violation';
                end if;

                return new;
            end;
            $$;

            create trigger project_statuses_protect_initial
            before insert or update of deleted_at, is_initial on project_statuses
            for each row
            execute function protect_initial_workflow_value();

            create trigger project_priorities_protect_initial
            before insert or update of deleted_at, is_initial on project_priorities
            for each row
            execute function protect_initial_workflow_value();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('project_priorities');
        Schema::dropIfExists('project_statuses');

        DB::statement('drop function if exists protect_initial_workflow_value()');
    }
};
