<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extensions and shared functions used by the domain tables
     * (docs/arquitectura/modelo-de-datos.md, sections 6, 8 and 9):
     * trigram search, GiST over ranges, accent-insensitive Spanish full-text
     * search and the guard that keeps history tables append-only.
     *
     * `migrate:fresh` drops tables but not functions or configurations, so
     * every statement here is idempotent.
     */
    public function up(): void
    {
        DB::statement('create extension if not exists pg_trgm');
        DB::statement('create extension if not exists btree_gist');
        DB::statement('create extension if not exists unaccent');

        DB::unprepared(<<<'SQL'
            do $$
            begin
                if not exists (select 1 from pg_ts_config where cfgname = 'es_unaccent') then
                    create text search configuration es_unaccent (copy = spanish);
                    alter text search configuration es_unaccent
                        alter mapping for hword, hword_part, word with unaccent, spanish_stem;
                end if;
            end
            $$;

            create or replace function immutable_tsvector(text)
            returns tsvector
            language sql
            immutable
            parallel safe
            as $$ select to_tsvector('public.es_unaccent'::regconfig, coalesce($1, '')) $$;

            create or replace function forbid_mutation()
            returns trigger
            language plpgsql
            as $$
            begin
                raise exception '% is append-only', tg_table_name
                    using errcode = 'check_violation';
            end;
            $$;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop function if exists forbid_mutation()');
        DB::statement('drop function if exists immutable_tsvector(text)');
        DB::statement('drop text search configuration if exists es_unaccent');
    }
};
