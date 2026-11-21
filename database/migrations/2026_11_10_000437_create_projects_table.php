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
     * A project belongs to a workspace and optionally to a client of the same
     * workspace (RN-02, RN-13, A-04). Its team holds admins and members only,
     * never client users (RN-21); task assignees reference this table, which
     * is how RN-18 reaches the database.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 32)->nullable();
            $table->boolean('is_visible_to_client')->default(false);
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'workspace_id']);
            $table->index('client_id');

            $table->foreign(['client_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('clients');
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['project_id', 'user_id']);
            $table->index(['user_id', 'project_id']);
            $table->index(['workspace_id', 'user_id']);

            $table->foreign(['project_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('projects')
                ->cascadeOnDelete();
            $table->foreign(['workspace_id', 'user_id'])
                ->references(['workspace_id', 'user_id'])->on('workspace_user')
                ->cascadeOnDelete();
        });

        DB::statement('create index projects_workspace_id_name_active_index on projects (workspace_id, name) where deleted_at is null');
        DB::statement('create index projects_name_trgm_index on projects using gin (name gin_trgm_ops) where deleted_at is null');
        DB::statement('create index projects_trash_index on projects (workspace_id, deleted_at) where deleted_at is not null');

        DB::unprepared(<<<'SQL'
            create or replace function project_user_requires_internal_role()
            returns trigger
            language plpgsql
            as $$
            begin
                if not exists (
                    select 1 from workspace_user
                    where workspace_id = new.workspace_id
                        and user_id = new.user_id
                        and role in ('admin', 'member')
                ) then
                    raise exception 'Only admins and members can join a project team (RN-18, RN-21)'
                        using errcode = 'check_violation';
                end if;

                return new;
            end;
            $$;

            create trigger project_user_requires_internal_role
            before insert or update on project_user
            for each row
            execute function project_user_requires_internal_role();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');

        DB::statement('drop function if exists project_user_requires_internal_role()');
    }
};
