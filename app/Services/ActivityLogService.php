<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use App\Support\Enums\ActorRoles;
use App\Support\Enums\UserRoles;

final class ActivityLogService
{
    public function logUserAction(
        User $actor,
        string $action,
        array $payload,
        ?Document $document = null,
        ?string $previousStatus = null,
        ?string $newStatus = null,
    ): ActivityLog {
        // Ensure actor role is one of the allowed role enums (defensive).
        UserRoles::assertValid($actor->role);

        return ActivityLog::create([
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'action' => $action,
            'document_id' => $document?->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'payload' => $payload,
        ]);
    }

    public function logSystemAction(
        string $action,
        array $payload,
        ?Document $document = null,
        ?string $previousStatus = null,
        ?string $newStatus = null,
    ): ActivityLog {
        return ActivityLog::create([
            'actor_id' => null,
            'actor_role' => ActorRoles::SYSTEM,
            'action' => $action,
            'document_id' => $document?->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'payload' => $payload,
        ]);
    }
}

