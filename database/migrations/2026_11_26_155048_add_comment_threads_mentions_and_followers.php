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
     * Comments get one-level threads and mentions, pages get comments, and
     * both tasks and pages get followers (RN-10, revisada 2026-11-26). Each
     * aggregate keeps its own tables with real foreign keys (D-07).
     */
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('task_id')->constrained('task_comments')->restrictOnDelete();
        });

        Schema::create('page_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('page_id');
            $table->foreignId('parent_id')->nullable()->constrained('page_comments')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestampTz('edited_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->foreign(['page_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])->on('pages');
        });

        Schema::create('task_comment_mentions', function (Blueprint $table) {
            $table->foreignId('task_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['task_comment_id', 'user_id']);
        });

        Schema::create('page_comment_mentions', function (Blueprint $table) {
            $table->foreignId('page_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['page_comment_id', 'user_id']);
        });

        Schema::create('task_followers', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_following')->default(true);
            $table->timestampsTz();

            $table->primary(['task_id', 'user_id']);
        });

        Schema::create('page_followers', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_following')->default(true);
            $table->timestampsTz();

            $table->primary(['page_id', 'user_id']);
        });

        DB::statement('alter table task_comments add constraint task_comments_parent_is_not_self check (parent_id is null or parent_id <> id)');
        DB::statement('alter table page_comments add constraint page_comments_parent_is_not_self check (parent_id is null or parent_id <> id)');

        DB::statement('create index task_comments_parent_id_created_at_index on task_comments (parent_id, created_at) where deleted_at is null and parent_id is not null');
        DB::statement('create index page_comments_page_id_created_at_index on page_comments (page_id, created_at) where deleted_at is null');
        DB::statement('create index page_comments_parent_id_created_at_index on page_comments (parent_id, created_at) where deleted_at is null and parent_id is not null');
        DB::statement('create index page_comments_trash_index on page_comments (workspace_id, deleted_at) where deleted_at is not null');
        DB::statement('create index task_comment_mentions_user_id_index on task_comment_mentions (user_id)');
        DB::statement('create index page_comment_mentions_user_id_index on page_comment_mentions (user_id)');
        DB::statement('create index task_followers_user_id_index on task_followers (user_id)');
        DB::statement('create index page_followers_user_id_index on page_followers (user_id)');

        foreach (['task' => 'task_id', 'page' => 'page_id'] as $owner => $ownerColumn) {
            DB::unprepared(<<<SQL
                create or replace function {$owner}_comments_enforce_single_level_thread()
                returns trigger
                language plpgsql
                as \$\$
                begin
                    if new.parent_id is null then
                        return new;
                    end if;

                    if not exists (
                        select 1 from {$owner}_comments p
                        where p.id = new.parent_id and p.parent_id is null and p.{$ownerColumn} = new.{$ownerColumn}
                    ) then
                        raise exception 'A reply must answer a root comment of the same {$owner}'
                            using errcode = 'check_violation';
                    end if;

                    return new;
                end;
                \$\$;

                create trigger {$owner}_comments_enforce_single_level_thread
                before insert or update of parent_id, {$ownerColumn} on {$owner}_comments
                for each row execute function {$owner}_comments_enforce_single_level_thread();
                SQL);
        }

        $this->createTrashView(withPageComments: true);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->createTrashView(withPageComments: false);

        Schema::dropIfExists('page_followers');
        Schema::dropIfExists('task_followers');
        Schema::dropIfExists('page_comment_mentions');
        Schema::dropIfExists('task_comment_mentions');
        Schema::dropIfExists('page_comments');
        DB::unprepared('drop function if exists page_comments_enforce_single_level_thread()');

        DB::unprepared('drop trigger if exists task_comments_enforce_single_level_thread on task_comments');
        DB::unprepared('drop function if exists task_comments_enforce_single_level_thread()');
        DB::statement('alter table task_comments drop constraint if exists task_comments_parent_is_not_self');

        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }

    /**
     * The trash view of 2026_11_10_000443, with or without page comments.
     * Mention tokens are hidden from comment titles.
     */
    private function createTrashView(bool $withPageComments): void
    {
        $commentTitle = fn (string $alias): string => $withPageComments
            ? "left(regexp_replace({$alias}.body, '@\\[[0-9a-fA-F-]{36}\\]', '@…', 'g'), 120)"
            : "left({$alias}.body, 120)";

        $pageComments = $withPageComments ? <<<SQL
            union all
            select 'page_comment', c.id, c.uuid, c.workspace_id, g.project_id,
                   {$commentTitle('c')}, c.deleted_at, c.deleted_by_id
            from page_comments c join pages g on g.id = c.page_id where c.deleted_at is not null
            SQL : '';

        DB::statement('drop view if exists trash_items');
        DB::statement(<<<SQL
            create view trash_items as
            select 'workspace'::text as type, w.id, w.uuid, w.id as workspace_id, null::bigint as project_id,
                   w.name::text as title, w.deleted_at, w.deleted_by_id
            from workspaces w where w.deleted_at is not null
            union all
            select 'client', c.id, c.uuid, c.workspace_id, null,
                   c.name, c.deleted_at, c.deleted_by_id
            from clients c where c.deleted_at is not null
            union all
            select 'project', p.id, p.uuid, p.workspace_id, p.id,
                   p.name, p.deleted_at, p.deleted_by_id
            from projects p where p.deleted_at is not null
            union all
            select 'project_status', s.id, null, p.workspace_id, s.project_id,
                   s.name, s.deleted_at, s.deleted_by_id
            from project_statuses s join projects p on p.id = s.project_id where s.deleted_at is not null
            union all
            select 'project_priority', r.id, null, p.workspace_id, r.project_id,
                   r.name, r.deleted_at, r.deleted_by_id
            from project_priorities r join projects p on p.id = r.project_id where r.deleted_at is not null
            union all
            select 'task_label', l.id, null, p.workspace_id, l.project_id,
                   l.name, l.deleted_at, l.deleted_by_id
            from task_labels l join projects p on p.id = l.project_id where l.deleted_at is not null
            union all
            select 'task', t.id, t.uuid, t.workspace_id, t.project_id,
                   t.name, t.deleted_at, t.deleted_by_id
            from tasks t where t.deleted_at is not null
            union all
            select 'task_comment', c.id, c.uuid, c.workspace_id, t.project_id,
                   {$commentTitle('c')}, c.deleted_at, c.deleted_by_id
            from task_comments c join tasks t on t.id = c.task_id where c.deleted_at is not null
            union all
            select 'task_attachment', a.id, a.uuid, a.workspace_id, t.project_id,
                   a.original_name, a.deleted_at, a.deleted_by_id
            from task_attachments a join tasks t on t.id = a.task_id where a.deleted_at is not null
            union all
            select 'page', g.id, g.uuid, g.workspace_id, g.project_id,
                   g.title, g.deleted_at, g.deleted_by_id
            from pages g where g.deleted_at is not null
            union all
            select 'page_file', f.id, f.uuid, f.workspace_id, g.project_id,
                   f.original_name, f.deleted_at, f.deleted_by_id
            from page_files f join pages g on g.id = f.page_id where f.deleted_at is not null
            {$pageComments}
            union all
            select 'wiki_label', l.id, null, l.workspace_id, null,
                   l.name, l.deleted_at, l.deleted_by_id
            from wiki_labels l where l.deleted_at is not null
            SQL);
    }
};
