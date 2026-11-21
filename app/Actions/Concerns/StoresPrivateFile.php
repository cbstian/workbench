<?php

namespace App\Actions\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores an uploaded file on the private disk under a UUIDv7 name and returns
 * the columns shared by task_attachments and page_files (D-07).
 */
trait StoresPrivateFile
{
    public const string DISK = 'local';

    /**
     * @return array{uuid: string, disk: string, path: string, original_name: string, mime_type: string|null, size_bytes: int, checksum_sha256: string}
     */
    protected function storePrivately(UploadedFile $file, string $directory): array
    {
        $uuid = (string) Str::uuid7();

        return [
            'uuid' => $uuid,
            'disk' => self::DISK,
            'path' => Storage::disk(self::DISK)->putFileAs($directory, $file, $uuid),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }
}
