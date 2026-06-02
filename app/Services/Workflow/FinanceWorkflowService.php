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

final class FinanceWorkflowService
{
    public function __construct(
        private ActivityLogService $logs,
    ) {}

    public function close(Document $document, User $actor): Document
    {
        if ($actor->role !== UserRoles::FINANCE) {
            throw new RuntimeException('Only Finance can close.');
        }

        $current = $document->status;
        if ($current !== DocumentStatuses::SPV_APPROVED) {
            throw new RuntimeException('Finance can close only from spv_approved.');
        }

        if ($current === DocumentStatuses::FINANCE_CLOSED) {
            throw new RuntimeException('Cannot process finance_closed document.');
        }

        $items = $document->items()->get();
        if ($items->count() === 0) {
            throw new RuntimeException('Document has no items.');
        }

        foreach ($items as $item) {
            if ($item->match_status === ItemMatchStatuses::TIDAK_SESUAI) {
                throw new RuntimeException('Finance cannot close if any item is tidak_sesuai.');
            }
        }

        return DB::transaction(function () use ($document, $actor, $current) {
            $fromStatus = $current;
            $toStatus = DocumentStatuses::FINANCE_CLOSED;

            $document->status = $toStatus;
            $document->finance_processed_at = now();
            $document->finance_processed_by = $actor->id;
            $document->save();

            DocumentDecision::create([
                'document_id' => $document->id,
                'decision_type' => DecisionTypes::FINANCE_CLOSE,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => null,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
            ]);

            $this->logs->logUserAction(
                actor: $actor,
                action: 'finance_close',
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
        if ($actor->role !== UserRoles::FINANCE) {
            throw new RuntimeException('Only Finance can reject.');
        }

        $current = $document->status;
        if ($current !== DocumentStatuses::SPV_APPROVED) {
            throw new RuntimeException('Finance can reject only from spv_approved.');
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
            $toStatus = DocumentStatuses::FINANCE_REJECTED;

            $document->status = $toStatus;
            $document->finance_processed_at = now();
            $document->finance_processed_by = $actor->id;
            $document->save();

            $decision = DocumentDecision::create([
                'document_id' => $document->id,
                'decision_type' => DecisionTypes::FINANCE_REJECT,
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
                action: 'finance_reject',
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

