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
     * One table holds both wikis: a page without project belongs to the
     * workspace wiki, otherwise to the project wiki (RN-09, RN-24). Subpages
     * stay in the parent's wiki and never form cycles. Files live inside
     * pages, wiki labels are per workspace and history is append-only.
     */
    public function up(): void
    {
        Schema::create('wiki_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('name', 64);
            $table->char('color', 7)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'workspace_id']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->restrictOnDelete();
            $table->string('title');
            $table->string('icon', 32)->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_visible_to_client')->default(false);
            $table->bigInteger('position')->default(0);
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['id', 'workspace_id']);

            $table->foreign(['project_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('projects');
        });

        Schema::create('page_wiki_label', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('wiki_label_id');

            $table->primary(['page_id', 'wiki_label_id']);
            $table->index(['wiki_label_id', 'page_id']);

            $table->foreign(['page_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('pages')
                ->cascadeOnDelete();
            $table->foreign(['wiki_label_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('wiki_labels')
                ->cascadeOnDelete();
        });

        Schema::create('page_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('page_id');
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

            $table->foreign(['page_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('pages');
        });

        Schema::create('page_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('page_id');
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event', 48);
            $table->jsonb('changes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign(['page_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('pages');
        });

        DB::statement(<<<'SQL'
            alter table pages add column search_vector tsvector
            generated always as (
                setweight(immutable_tsvector(title), 'A')
                || setweight(immutable_tsvector(regexp_replace(coalesce(body, ''), '<[^>]+>', ' ', 'g')), 'B')
            ) stored
            SQL);

        DB::statement("alter table wiki_labels add constraint wiki_labels_color_hex check (color ~ '^#[0-9a-fA-F]{6}$')");
        DB::statement('alter table pages add constraint pages_parent_is_not_self check (parent_id is null or parent_id <> id)');
        DB::statement('alter table page_files add constraint page_files_size_bytes_positive check (size_bytes >= 0)');

        DB::statement('create unique index wiki_labels_workspace_id_name_active_unique on wiki_labels (workspace_id, lower(name)) where deleted_at is null');
        DB::statement('create index wiki_labels_trash_index on wiki_labels (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index pages_tree_index on pages (workspace_id, project_id, parent_id, position) where deleted_at is null');
        DB::statement('create index pages_parent_id_index on pages (parent_id)');
        DB::statement('create index pages_project_id_index on pages (project_id) where project_id is not null');
        DB::statement('create index pages_search_vector_index on pages using gin (search_vector)');
        DB::statement('create index pages_trash_index on pages (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index page_files_page_id_index on page_files (page_id) where deleted_at is null');
        DB::statement('create index page_files_trash_index on page_files (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index page_activities_page_id_created_at_index on page_activities (page_id, created_at desc)');
        DB::statement('create index page_activities_created_at_brin on page_activities using brin (created_at)');

        DB::unprepared(<<<'SQL'
            create or replace function pages_enforce_same_wiki_parent()
            returns trigger
            language plpgsql
            as $$
            declare
                parent_workspace_id bigint;
                parent_project_id bigint;
            begin
                if new.parent_id is null then
                    return new;
                end if;

                select workspace_id, project_id
                into parent_workspace_id, parent_project_id
                from pages
                where id = new.parent_id;

                if not found
                    or parent_workspace_id is distinct from new.workspace_id
                    or parent_project_id is distinct from new.project_id then
                    raise exception 'A subpage must belong to the same wiki as its parent'
                        using errcode = 'check_violation';
                end if;

                if tg_op = 'UPDATE' and exists (
                    with recursive ancestors as (
                        select id, parent_id from pages where id = new.parent_id
                        union all
                        select p.id, p.parent_id from pages p join ancestors a on p.id = a.parent_id
                    )
                    select 1 from ancestors where id = new.id
                ) then
                    raise exception 'A page cannot be moved under one of its own subpages'
                        using errcode = 'check_violation';
                end if;

                return new;
            end;
            $$;

            create trigger pages_enforce_same_wiki_parent
            before insert or update of parent_id, workspace_id, project_id
            on pages
            for each row
            execute function pages_enforce_same_wiki_parent();

            create trigger page_activities_append_only
            before update or delete on page_activities
            for each row
            execute function forbid_mutation();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_activities');
        Schema::dropIfExists('page_files');
        Schema::dropIfExists('page_wiki_label');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('wiki_labels');

        DB::statement('drop function if exists pages_enforce_same_wiki_parent()');
    }
};
