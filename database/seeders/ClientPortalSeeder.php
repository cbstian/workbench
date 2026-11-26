<?php

namespace Database\Seeders;

use App\Actions\Comments\PostComment;
use App\Enums\CommentThread;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientPortalSeeder extends Seeder
{
    /**
     * Give the demo client user (cliente@workbench.test) something to see in
     * the portal: two projects of their client shared with them, a few wiki
     * pages marked visible (and one internal) and a short conversation.
     * Runs once: it stops when the conversation already exists.
     */
    public function run(PostComment $postComment): void
    {
        $clientUser = User::query()->where('email', 'cliente@workbench.test')->first();
        $client = $clientUser?->clients()->first();
        $owner = $client?->workspace->account->owner;

        if ($client === null || $owner === null) {
            return;
        }

        $projects = $client->projects()->orderBy('name')->limit(2)->get();

        if ($projects->isEmpty() || $projects->contains(fn (Project $project): bool => $project->tasks()->whereHas('comments', fn ($query) => $query->where('thread', CommentThread::Client))->exists())) {
            return;
        }

        $projects->each(fn (Project $project) => $project->update(['is_visible_to_client' => true]));

        $project = $projects->first();

        $plan = Page::factory()->forProject($project)->visibleToClient()->create([
            'title' => 'Plan de trabajo',
            'icon' => '🗺️',
            'body' => "## Objetivo\n\nOrdenar las entregas del proyecto y dejar claro quién hace qué.\n\n## Etapas\n\n1. Levantamiento\n2. Diseño\n3. Implementación\n4. Revisión con el cliente\n\n> Cualquier duda, escríbenos en la conversación de esta página.",
            'position' => 1024,
            'created_by_id' => $owner->id,
        ]);

        Page::factory()->childOf($plan)->visibleToClient()->create([
            'title' => 'Entregables',
            'icon' => '📦',
            'body' => "- Informe de levantamiento\n- Propuesta de diseño\n- Versión final",
            'created_by_id' => $owner->id,
        ]);

        Page::factory()->forProject($project)->create([
            'title' => 'Notas internas',
            'icon' => '🔒',
            'body' => 'Solo para el equipo.',
            'position' => 2048,
            'created_by_id' => $owner->id,
        ]);

        Page::factory()->visibleToClient()->create([
            'workspace_id' => $client->workspace_id,
            'title' => 'Cómo trabajamos',
            'icon' => '👋',
            'body' => "Bienvenido a tu portal. Aquí ves el avance de tus proyectos en kanban, listado o calendario, lees la documentación y comentas cada tarea o página.\n\nEl equipo responde en la misma conversación.",
            'created_by_id' => $owner->id,
        ]);

        $task = $project->rootTasks()->orderBy('position')->first();

        if ($task !== null) {
            $question = $postComment->handle($task, $clientUser, '¿Podemos revisar esta tarea en la reunión del jueves?', thread: CommentThread::Client);
            $postComment->handle($task, $owner, 'Claro, la dejamos en la agenda.', replyTo: $question);
            $postComment->handle($task, $owner, 'Revisar presupuesto antes de mostrarle al cliente.');
        }
    }
}
