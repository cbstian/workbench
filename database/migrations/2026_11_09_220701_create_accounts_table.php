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
     * An account is the scope of one super admin (glossary: «Cuenta»). Owning
     * an account is what makes a user a super admin (RN-01). Users point to
     * the account that created and manages them, which bounds who may
     * deactivate or delete them (RN-14).
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->foreignId('owner_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->timestampsTz();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestampTz('deactivated_at')->nullable();
            $table->foreignId('deactivated_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->softDeletesTz();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropConstrainedForeignId('deactivated_by_id');
            $table->dropConstrainedForeignId('deleted_by_id');
            $table->dropColumn(['deactivated_at', 'deleted_at']);
        });

        Schema::dropIfExists('accounts');
    }
};
