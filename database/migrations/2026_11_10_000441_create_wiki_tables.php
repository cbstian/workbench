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
     * A page always belongs to a workspace. project_id null is the workspace
     * wiki; a value is the project wiki (RN-09). Subpages may nest.
     */
    public function up(): void
    {
        Schema::create('wiki_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->restrictOnDelete();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->boolean('is_visible_to_client')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'project_id']);
            $table->index('parent_id');

            $table->foreign(['project_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])
                ->on('projects');
        });

        Schema::create('page_wiki_label', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wiki_label_id')->constrained()->cascadeOnDelete();

            $table->primary(['page_id', 'wiki_label_id']);
            $table->index('wiki_label_id');
        });

        Schema::create('page_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->softDeletes();

            $table->index('page_id');
        });

        Schema::create('page_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('event');
            $table->jsonb('properties')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('page_id');
        });

        DB::statement('create unique index wiki_labels_workspace_id_name_active_unique on wiki_labels (workspace_id, name) where deleted_at is null');
        DB::statement('alter table pages add constraint pages_parent_is_not_self check (parent_id is null or parent_id <> id)');

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
                    raise exception 'A subpage must belong to the same wiki as its parent';
                end if;

                return new;
            end;
            $$;

            create trigger pages_enforce_same_wiki_parent
            before insert or update of parent_id, workspace_id, project_id
            on pages
            for each row
            execute function pages_enforce_same_wiki_parent();
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

        DB::unprepared('drop function if exists pages_enforce_same_wiki_parent()');
    }
};
