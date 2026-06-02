<?php

namespace App\Services\Workflow;

use App\Models\Document;
use App\Models\DocumentDecision;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AdminWorkflowService
{
    public function __construct(
        private ActivityLogService $logs,
    ) {}

    public function overrideStatus(Document $document, User $actor, string $toStatus, string $reason): Document
    {
        if ($actor->role !== UserRoles::ADMIN) {
            throw new RuntimeException('Only Admin can override.');
        }

        $toStatus = trim($toStatus);
        if ($toStatus === '') {
            throw new RuntimeException('Target status is required.');
        }

        DocumentStatuses::assertValid($toStatus);

        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('Override reason is required.');
        }

        return DB::transaction(function () use ($document, $actor, $toStatus, $reason) {
            $fromStatus = $document->status;

            $document->status = $toStatus;
            $document->admin_overridden_at = now();
            $document->admin_overridden_by = $actor->id;
            $document->save();

            DocumentDecision::create([
                'document_id' => $document->id,
                'decision_type' => DecisionTypes::ADMIN_OVERRIDE,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
            ]);

            $this->logs->logUserAction(
                actor: $actor,
                action: 'admin_override',
                payload: [
                    'document_id' => $document->id,
                    'reason' => $reason,
                    'to_status' => $toStatus,
                ],
                document: $document,
                previousStatus: $fromStatus,
                newStatus: $toStatus,
            );

            return $document->refresh();
        });
    }
}

