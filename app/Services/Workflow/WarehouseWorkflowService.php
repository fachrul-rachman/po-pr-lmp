<?php

namespace App\Services\Workflow;

use App\Models\Document;
use App\Models\DocumentDecision;
use App\Services\ActivityLogService;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WarehouseWorkflowService
{
    public function __construct(
        private ActivityLogService $logs,
    ) {}

    public function submit(Document $document, User $actor): Document
    {
        return $this->submitInternal($document, $actor, isResubmit: false);
    }

    public function resubmit(Document $document, User $actor): Document
    {
        return $this->submitInternal($document, $actor, isResubmit: true);
    }

    private function submitInternal(Document $document, User $actor, bool $isResubmit): Document
    {
        if ($actor->role !== UserRoles::WAREHOUSE) {
            throw new RuntimeException('Only Warehouse can submit.');
        }

        $current = $document->status;

        if ($isResubmit) {
            if ($current !== DocumentStatuses::SPV_REJECTED) {
                throw new RuntimeException('Warehouse can resubmit only when status is spv_rejected.');
            }
        } else {
            // First submit: status should still be null (pre-submission stage).
            if ($current !== null) {
                throw new RuntimeException('Document is already in workflow and cannot be submitted again.');
            }
        }

        if ($current === DocumentStatuses::FINANCE_CLOSED) {
            throw new RuntimeException('Cannot submit finance_closed document.');
        }

        $items = $document->items()->withCount('photos')->get();
        if ($items->count() === 0) {
            throw new RuntimeException('Document has no items.');
        }

        foreach ($items as $item) {
            if ($item->match_status === null) {
                throw new RuntimeException('Every item must be checked before submit.');
            }

            if (! in_array($item->match_status, ItemMatchStatuses::all(), true)) {
                throw new RuntimeException('Invalid item match status.');
            }

            if ($item->match_status === ItemMatchStatuses::TIDAK_SESUAI) {
                if (! is_string($item->warehouse_reason) || trim($item->warehouse_reason) === '') {
                    throw new RuntimeException('Reason is required for tidak_sesuai item.');
                }
            }

            if ((int) $item->photos_count < 1) {
                throw new RuntimeException('Every item must have at least one photo before submit.');
            }
        }

        return DB::transaction(function () use ($document, $actor, $current, $isResubmit) {
            $fromStatus = $current;
            $toStatus = DocumentStatuses::WAREHOUSE_SUBMITTED;
            $decisionType = $isResubmit ? DecisionTypes::WAREHOUSE_RESUBMIT : DecisionTypes::WAREHOUSE_SUBMIT;
            $action = $isResubmit ? 'warehouse_resubmit' : 'warehouse_submit';

            if ($isResubmit) {
                $this->logs->logUserAction(
                    actor: $actor,
                    action: 'warehouse_edit_rejected_document',
                    payload: [
                        'document_id' => $document->id,
                    ],
                    document: $document,
                    previousStatus: $fromStatus,
                    newStatus: $fromStatus,
                );
            }

            $document->status = $toStatus;
            $document->warehouse_submitted_at = now();
            $document->warehouse_submitted_by = $actor->id;
            $document->save();

            DocumentDecision::create([
                'document_id' => $document->id,
                'decision_type' => $decisionType,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => null,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
            ]);

            $this->logs->logUserAction(
                actor: $actor,
                action: $action,
                payload: [
                    'document_id' => $document->id,
                ],
                document: $document,
                previousStatus: $fromStatus,
                newStatus: $toStatus,
            );

            return $document->refresh();
        });
    }
}

