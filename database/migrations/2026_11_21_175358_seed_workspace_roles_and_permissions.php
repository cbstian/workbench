<?php

use App\Actions\Authorization\SyncRolePermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Workspace roles (admin, member, client) are global spatie roles with
     * team_id null; a user receives one per workspace (team) in sync with
     * workspace_user.role (docs/arquitectura/modelo-de-datos.md, D-04).
     */
    public function up(): void
    {
        app(SyncRolePermissions::class)->handle();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table(config('permission.table_names.model_has_roles'))->delete();
        DB::table(config('permission.table_names.roles'))->delete();
        DB::table(config('permission.table_names.permissions'))->delete();
    }
};
