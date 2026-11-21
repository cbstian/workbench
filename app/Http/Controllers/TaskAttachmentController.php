<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves task attachments from the private disk to whoever may see the task.
 */
class TaskAttachmentController extends Controller
{
    public function show(TaskAttachment $attachment): StreamedResponse
    {
        abort_if($attachment->task === null, 404);
        Gate::authorize('view', $attachment->task);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name, array_filter([
            'Content-Type' => $attachment->mime_type,
        ]));
    }
}
