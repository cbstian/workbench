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
     * Tasks and pages get two conversations (P-04, decidida 2026-11-26): the
     * team's internal thread and the one with the client. Existing comments
     * stay internal. A reply keeps the thread of its root, checked by trigger.
     */
    public function up(): void
    {
        foreach (['task' => 'task_id', 'page' => 'page_id'] as $owner => $ownerColumn) {
            Schema::table("{$owner}_comments", function (Blueprint $table) {
                $table->text('thread')->default('internal')->after('parent_id');
            });

            DB::statement("alter table {$owner}_comments add constraint {$owner}_comments_thread_valid check (thread in ('internal', 'client'))");
            DB::statement("create index {$owner}_comments_{$ownerColumn}_thread_index on {$owner}_comments ({$ownerColumn}, thread) where deleted_at is null");

            DB::unprepared(<<<SQL
                create or replace function {$owner}_comments_reply_keeps_thread()
                returns trigger
                language plpgsql
                as \$\$
                begin
                    if new.parent_id is not null and not exists (
                        select 1 from {$owner}_comments p where p.id = new.parent_id and p.thread = new.thread
                    ) then
                        raise exception 'A reply must stay in the thread of its root comment'
                            using errcode = 'check_violation';
                    end if;

                    return new;
                end;
                \$\$;

                create trigger {$owner}_comments_reply_keeps_thread
                before insert or update of parent_id, thread on {$owner}_comments
                for each row execute function {$owner}_comments_reply_keeps_thread();
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['task' => 'task_id', 'page' => 'page_id'] as $owner => $ownerColumn) {
            DB::unprepared("drop trigger if exists {$owner}_comments_reply_keeps_thread on {$owner}_comments");
            DB::unprepared("drop function if exists {$owner}_comments_reply_keeps_thread()");
            DB::statement("drop index if exists {$owner}_comments_{$ownerColumn}_thread_index");
            DB::statement("alter table {$owner}_comments drop constraint if exists {$owner}_comments_thread_valid");

            Schema::table("{$owner}_comments", function (Blueprint $table) {
                $table->dropColumn('thread');
            });
        }
    }
};
