<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The trash bin (RN-19) is a read-only view over every soft-deletable
     * table, so nothing is copied or kept in sync. Only the rows a user
     * deleted show up: children of a trashed project stay hidden through it
     * without being marked themselves.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
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
                   left(c.body, 120), c.deleted_at, c.deleted_by_id
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
            union all
            select 'wiki_label', l.id, null, l.workspace_id, null,
                   l.name, l.deleted_at, l.deleted_by_id
            from wiki_labels l where l.deleted_at is not null
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop view if exists trash_items');
    }
};
