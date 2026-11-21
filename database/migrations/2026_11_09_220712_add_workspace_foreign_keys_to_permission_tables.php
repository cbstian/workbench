<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Spatie teams are workspaces (docs/permisos.md). team_id stays nullable
     * on roles so a later global role can omit it. A physical workspace delete
     * removes the team rows; soft deletes do not.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $column = config('permission.column_names.team_foreign_key');

        foreach ([$tableNames['roles'], $tableNames['model_has_roles'], $tableNames['model_has_permissions']] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($column): void {
                $table->foreign($column)
                    ->references('id')
                    ->on('workspaces')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $column = config('permission.column_names.team_foreign_key');

        foreach ([$tableNames['roles'], $tableNames['model_has_roles'], $tableNames['model_has_permissions']] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($column): void {
                $table->dropForeign([$column]);
            });
        }
    }
};
