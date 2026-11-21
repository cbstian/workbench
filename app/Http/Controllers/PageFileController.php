<?php

namespace App\Http\Controllers;

use App\Models\PageFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves wiki page files from the private disk to whoever reaches the page.
 */
class PageFileController extends Controller
{
    public function show(PageFile $file): StreamedResponse
    {
        abort_if($file->page === null, 404);
        Gate::authorize('view', $file->page);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        return $disk->download($file->path, $file->original_name, array_filter([
            'Content-Type' => $file->mime_type,
        ]));
    }
}
