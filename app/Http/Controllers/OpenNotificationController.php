<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Task;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TallStackUi\Traits\Interactions;

/**
 * Opens an inbox alert (RN-10): marks it read, moves to the workspace of the
 * task or page when needed and lands on the comment.
 */
class OpenNotificationController extends Controller
{
    use Interactions;

    public function __invoke(Request $request, string $notification, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $user = $request->user();
        $alert = $user->notifications()->whereKey($notification)->firstOrFail();
        $alert->markAsRead();

        $data = $alert->data;

        $commentable = match ($data['commentable_type'] ?? null) {
            'task' => Task::query()->where('uuid', $data['commentable_uuid'] ?? '')->first(),
            'page' => Page::query()->where('uuid', $data['commentable_uuid'] ?? '')->first(),
            default => null,
        };

        if ($commentable === null || $user->cannot('view', $commentable)) {
            $this->toast()->warning('Ya no está disponible', 'La tarea o página se eliminó o ya no tienes acceso.')->send();

            return redirect()->route('notifications.index');
        }

        if ($currentWorkspace->get()?->id !== $commentable->workspace_id) {
            $workspace = Workspace::query()->visibleTo($user)->whereKey($commentable->workspace_id)->firstOrFail();
            $currentWorkspace->switchTo($workspace);
        }

        return redirect()->to($commentable->commentableUrl().'#comment-'.($data['comment_uuid'] ?? ''));
    }
}
