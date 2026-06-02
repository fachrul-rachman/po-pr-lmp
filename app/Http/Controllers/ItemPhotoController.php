<?php

namespace App\Http\Controllers;

use App\Models\ItemPhoto;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ItemPhotoController extends Controller
{
    public function show(Request $request, ItemPhoto $photo)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $photo->loadMissing('documentItem.document');
        $doc = $photo->documentItem?->document;
        if (! $doc) {
            abort(404);
        }

        // Enforce document visibility by role (see WORKFLOW.md / ROLE-PERMISSION.md).
        $status = $doc->status;
        $role = $user->role;

        if ($role === UserRoles::ADMIN || $role === UserRoles::PURCHASING) {
            // Allowed to view all documents.
        } elseif ($role === UserRoles::WAREHOUSE) {
            // Warehouse can view drafts (status null) and submitted docs through its pages.
        } elseif ($role === UserRoles::SPV) {
            if ($status === null) {
                abort(403);
            }
        } elseif ($role === UserRoles::FINANCE) {
            if (! in_array($status, [DocumentStatuses::SPV_APPROVED, DocumentStatuses::FINANCE_REJECTED, DocumentStatuses::FINANCE_CLOSED], true)) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $disk = $photo->disk;
        $path = $photo->path;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $stream = Storage::disk($disk)->readStream($path);
        if ($stream === false) {
            abort(404);
        }

        $mime = $photo->mime_type ?: 'application/octet-stream';
        $filename = $photo->original_name ?: 'photo';

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

