<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the color palette chosen by each user (App\Enums\ColorPalette).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('palette', 20)->default('roble')->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('palette');
        });
    }
};
