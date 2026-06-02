<?php

namespace App\Services\Workflow;

use App\Models\DecisionItemReason;
use App\Models\Document;
use App\Models\DocumentDecision;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SpvWorkflowService
{
    public function __construct(
        private ActivityLogService $logs,
    ) {}

    public function approve(Document $document, User $actor): Document
    {
        if ($actor->role !== UserRoles::SPV) {
            throw new RuntimeException('Only SPV can approve.');
        }

        $current = $document->status;
        if (! in_array($current, [DocumentStatuses::WAREHOUSE_SUBMITTED, DocumentStatuses::FINANCE_REJECTED], true)) {
            throw new RuntimeException('SPV can approve only from warehouse_submitted or finance_rejected.');
        }

        if ($current === DocumentStatuses::FINANCE_CLOSED) {
            throw new RuntimeException('Cannot process finance_closed document.');
        }

        $items = $document->items()->withCount('photos')->get();
        if ($items->count() === 0) {
            throw new RuntimeException('Document has no items.');
        }

        foreach ($items as $item) {
            if ($item->match_status !== ItemMatchStatuses::SESUAI) {
                // Covers null and tidak_sesuai.
                throw new RuntimeException('SPV cannot approve if any item is tidak_sesuai.');
            }

            if ((int) $item->photos_count < 1) {
                throw new RuntimeException('Every item must have at least one photo before SPV approve.');
            }
        }

        return DB::transaction(function () use ($document, $actor, $current) {
            $fromStatus = $current;
            $toStatus = DocumentStatuses::SPV_APPROVED;

            $document->status = $toStatus;
            $document->spv_processed_at = now();
            $document->spv_processed_by = $actor->id;
            $document->save();

            DocumentDecision::create([
                'document_id' => $document->id,
                'decision_type' => DecisionTypes::SPV_APPROVE,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => null,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
            ]);

            $this->logs->logUserAction(
                actor: $actor,
                action: 'spv_approve',
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

    /**
     * @param  array<string, string>  $itemReasons  document_item_id => reason
     */
    public function reject(Document $document, User $actor, string $reason, array $itemReasons = []): Document
    {
        if ($actor->role !== UserRoles::SPV) {
            throw new RuntimeException('Only SPV can reject.');
        }

        $current = $document->status;
        if (! in_array($current, [DocumentStatuses::WAREHOUSE_SUBMITTED, DocumentStatuses::FINANCE_REJECTED], true)) {
            throw new RuntimeException('SPV can reject only from warehouse_submitted or finance_rejected.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('Document-level rejection reason is required.');
        }

        if ($current === DocumentStatuses::FINANCE_CLOSED) {
            throw new RuntimeException('Cannot process finance_closed document.');
        }

        return DB::transaction(function () use ($document, $actor, $current, $reason, $itemReasons) {
            $fromStatus = $current;
            $toStatus = DocumentStatuses::SPV_REJECTED;

            $document->status = $toStatus;
            $document->spv_processed_at = now();
            $document->spv_processed_by = $actor->id;
            $document->save();

            $decision = DocumentDecision::create([
                'document_id' => $document->id,
                'decision_type' => DecisionTypes::SPV_REJECT,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
            ]);

            foreach ($itemReasons as $itemId => $itemReason) {
                if (! is_string($itemId)) {
                    continue;
                }

                $itemReason = trim((string) $itemReason);
                if ($itemReason === '') {
                    continue;
                }

                DecisionItemReason::create([
                    'document_decision_id' => $decision->id,
                    'document_item_id' => $itemId,
                    'reason' => $itemReason,
                ]);
            }

            $this->logs->logUserAction(
                actor: $actor,
                action: 'spv_reject',
                payload: [
                    'document_id' => $document->id,
                    'reason' => $reason,
                    'item_reason_count' => count(array_filter($itemReasons, fn ($v) => is_string($v) && trim($v) !== '')),
                ],
                document: $document,
                previousStatus: $fromStatus,
                newStatus: $toStatus,
            );

            return $document->refresh();
        });
    }
}

