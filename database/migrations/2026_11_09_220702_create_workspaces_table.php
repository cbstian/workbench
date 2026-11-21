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
     * The workspace is the tenant boundary: every content table carries its
     * `workspace_id`. The account owner is the super admin and has no
     * membership row; `workspace_user.role` is the single source of truth for
     * the role of everybody else (RN-03, RN-15).
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();
        });

        Schema::create('workspace_user', function (Blueprint $table) {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->primary(['workspace_id', 'user_id']);
            $table->index('user_id');
            $table->index(['workspace_id', 'role']);
        });

        DB::statement("alter table workspace_user add constraint workspace_user_role_valid check (role in ('admin', 'member', 'client'))");
        DB::statement('create index workspaces_account_id_active_index on workspaces (account_id) where deleted_at is null');
        DB::statement('create index workspaces_trash_index on workspaces (account_id, deleted_at) where deleted_at is not null');

        DB::unprepared(<<<'SQL'
            create or replace function workspace_user_guard_role_change()
            returns trigger
            language plpgsql
            as $$
            begin
                if new.role = 'client' and exists (
                    select 1 from project_user
                    where workspace_id = new.workspace_id and user_id = new.user_id
                ) then
                    raise exception 'A project team member cannot become a client user'
                        using errcode = 'check_violation';
                end if;

                if new.role <> 'client' and exists (
                    select 1 from client_user
                    where workspace_id = new.workspace_id and user_id = new.user_id
                ) then
                    raise exception 'A user linked to clients must keep the client role'
                        using errcode = 'check_violation';
                end if;

                return new;
            end;
            $$;

            create trigger workspace_user_guard_role_change
            before update of role on workspace_user
            for each row
            when (old.role is distinct from new.role)
            execute function workspace_user_guard_role_change();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');

        DB::statement('drop function if exists workspace_user_guard_role_change()');
    }
};
