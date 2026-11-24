<?php

use App\Actions\Authorization\SyncRolePermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Grants board.update to admins and members (docs/permisos.md). Idempotent.
     */
    public function up(): void
    {
        app(SyncRolePermissions::class)->handle();
    }

    /**
     * Reverse the migrations.
     *
     * Syncs again from the permission enum, so a rolled-back catalog drops the permission.
     */
    public function down(): void
    {
        app(SyncRolePermissions::class)->handle();
    }
};
