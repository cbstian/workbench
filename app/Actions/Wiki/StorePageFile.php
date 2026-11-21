<?php

namespace App\Actions\Wiki;

use App\Actions\Concerns\StoresPrivateFile;
use App\Enums\PageActivityEvent;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\PageFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Stores a file inside a wiki page (RN-09: no separate library) on the
 * private disk, at workspaces/{workspace_uuid}/pages/{page_uuid}/{uuid}.
 */
class StorePageFile
{
    use StoresPrivateFile;

    public function handle(Page $page, User $actor, UploadedFile $file): PageFile
    {
        $stored = $this->storePrivately($file, "workspaces/{$page->workspace->uuid}/pages/{$page->uuid}");

        return DB::transaction(function () use ($page, $actor, $stored): PageFile {
            $pageFile = PageFile::query()->create([...$stored, 'page_id' => $page->id, 'uploaded_by_id' => $actor->id]);

            PageActivity::record($page, $actor, PageActivityEvent::FileAdded, metadata: [
                'file_id' => $pageFile->id,
                'file_name' => $pageFile->original_name,
            ]);

            return $pageFile;
        });
    }
}
