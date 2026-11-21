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
     * A client lives inside a workspace and has zero or many projects (RN-02,
     * RN-12). Client users are workspace members with the client role and may
     * belong to several clients (RN-04). Composite foreign keys keep a client
     * user and their client in the same workspace.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path', 512)->nullable();
            $table->char('color', 7)->nullable();
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'workspace_id']);
        });

        Schema::create('client_user', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['client_id', 'user_id']);
            $table->index(['workspace_id', 'user_id']);

            $table->foreign(['client_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('clients')
                ->cascadeOnDelete();
            $table->foreign(['workspace_id', 'user_id'])
                ->references(['workspace_id', 'user_id'])->on('workspace_user')
                ->cascadeOnDelete();
        });

        DB::statement("alter table clients add constraint clients_color_hex check (color ~ '^#[0-9a-fA-F]{6}$')");
        DB::statement('create unique index clients_workspace_id_name_active_unique on clients (workspace_id, lower(name)) where deleted_at is null');
        DB::statement('create index clients_name_trgm_index on clients using gin (name gin_trgm_ops) where deleted_at is null');
        DB::statement('create index clients_trash_index on clients (workspace_id, deleted_at) where deleted_at is not null');

        DB::unprepared(<<<'SQL'
            create or replace function client_user_requires_client_role()
            returns trigger
            language plpgsql
            as $$
            begin
                if not exists (
                    select 1 from workspace_user
                    where workspace_id = new.workspace_id
                        and user_id = new.user_id
                        and role = 'client'
                ) then
                    raise exception 'Only client users can be linked to a client (RN-04)'
                        using errcode = 'check_violation';
                end if;

                return new;
            end;
            $$;

            create trigger client_user_requires_client_role
            before insert or update on client_user
            for each row
            execute function client_user_requires_client_role();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_user');
        Schema::dropIfExists('clients');

        DB::statement('drop function if exists client_user_requires_client_role()');
    }
};
