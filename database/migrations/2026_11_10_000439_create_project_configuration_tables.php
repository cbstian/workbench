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
     * is_initial marks the status and priority a new task takes (RN-22).
     * The partial unique index allows a trashed initial row to stay in the
     * trash while another row becomes the initial one.
     */
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['project_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('position');
            $table->boolean('is_initial')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
        });

        Schema::create('project_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('position');
            $table->boolean('is_initial')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
        });

        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index project_statuses_one_initial_per_project on project_statuses (project_id) where is_initial and deleted_at is null');
        DB::statement('create unique index project_priorities_one_initial_per_project on project_priorities (project_id) where is_initial and deleted_at is null');
        DB::statement('create unique index task_labels_project_id_name_active_unique on task_labels (project_id, name) where deleted_at is null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('project_priorities');
        Schema::dropIfExists('project_statuses');
        Schema::dropIfExists('project_user');
    }
};
