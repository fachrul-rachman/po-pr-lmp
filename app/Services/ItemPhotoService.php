<?php

namespace App\Services;

use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ItemPhotoService
{
    public function upload(DocumentItem $item, UploadedFile $file, User $actor): ItemPhoto
    {
        $size = (int) ($file->getSize() ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('Invalid upload: empty file.');
        }

        $bucket = (string) config('filesystems.disks.r2.bucket');
        $endpoint = (string) config('filesystems.disks.r2.endpoint');
        if ($bucket === '' || $endpoint === '') {
            throw new RuntimeException('Upload failed: R2 is not configured (missing R2_BUCKET or R2_ENDPOINT).');
        }

        $originalName = (string) ($file->getClientOriginalName() ?: 'upload');
        $mimeType = (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream');
        $ext = (string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        $filename = (string) Str::uuid().'.'.$ext;
        $dir = "item-photos/{$item->id}";

        try {
            $path = Storage::disk('r2')->putFileAs($dir, $file, $filename);
            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Upload failed: storage returned empty path.');
            }
        } catch (\Throwable $e) {
            // Avoid leaking secrets; report details to logs, show a concise actionable message to user.
            report($e);

            $msg = (string) $e->getMessage();
            if (stripos($msg, 'AccessDenied') !== false) {
                throw new RuntimeException('Upload failed: R2 AccessDenied. Pastikan API token punya izin write (PutObject) untuk bucket.');
            }

            $hint = 'Cek R2_ACCESS_KEY_ID/R2_SECRET_ACCESS_KEY, R2_BUCKET, R2_ENDPOINT, dan R2_USE_PATH_STYLE_ENDPOINT.';
            throw new RuntimeException('Upload failed: unable to store file. '.$hint);
        }

        return DB::transaction(function () use ($item, $actor, $path, $originalName, $mimeType, $size) {
            return ItemPhoto::create([
                'document_item_id' => $item->id,
                'uploaded_by' => $actor->id,
                'disk' => 'r2',
                'path' => $path,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
            ]);
        });
    }

    public function delete(ItemPhoto $photo): void
    {
        if ($photo->disk !== 'r2') {
            throw new RuntimeException('Invalid disk for item photo deletion.');
        }

        $ok = Storage::disk('r2')->delete($photo->path);
        if (! $ok) {
            throw new RuntimeException('Delete failed: unable to remove stored object.');
        }

        DB::transaction(function () use ($photo) {
            $photo->delete();
        });
    }

    public function replace(ItemPhoto $oldPhoto, UploadedFile $newFile, User $actor): ItemPhoto
    {
        // Safer order: upload new first, then delete old.
        $item = $oldPhoto->documentItem()->firstOrFail();
        $newPhoto = $this->upload($item, $newFile, $actor);

        // Best-effort cleanup of old after new succeeds.
        $this->delete($oldPhoto);

        return $newPhoto;
    }
}
