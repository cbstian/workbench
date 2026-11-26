<?php

use App\Actions\Authorization\SyncRolePermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Grants portal.view and portal.comment to the client role (P-05,
     * decidida 2026-11-26: acceso fijo «ver, comentar»). Idempotent.
     */
    public function up(): void
    {
        app(SyncRolePermissions::class)->handle();
    }

    /**
     * Reverse the migrations.
     *
     * Syncs again from the permission enum, so a rolled-back catalog drops the permissions.
     */
    public function down(): void
    {
        app(SyncRolePermissions::class)->handle();
    }
};
