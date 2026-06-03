<?php

namespace App\Livewire\Spv\Documents;

use App\Models\DecisionItemReason;
use App\Models\Document;
use App\Support\Enums\DecisionTypes;
use App\Services\Workflow\SpvWorkflowService;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowPage extends Component
{
    public Document $document;

    public bool $showApproveModal = false;
    public bool $showRejectModal = false;
    public bool $showPhotoModal = false;

    public ?string $previewPhotoId = null;

    public string $rejectReason = '';

    /** @var array<string, string> */
    public array $itemReasons = [];

    public ?string $financeRejectReason = null;

    /** @var array<int, array{item_name:string, reason:string}> */
    public array $financeItemReasons = [];

    public function mount(Document $document): void
    {
        $this->document = $document;

        // Pre-fill item reasons from latest SPV reject decision (if any), as a convenience.
        $latestReject = $document->decisions()
            ->where('decision_type', 'spv_reject')
            ->latest('created_at')
            ->first();

        if ($latestReject) {
            $this->rejectReason = (string) ($latestReject->reason ?? '');

            $reasons = DecisionItemReason::query()
                ->where('document_decision_id', $latestReject->id)
                ->get();

            foreach ($reasons as $r) {
                $this->itemReasons[$r->document_item_id] = (string) $r->reason;
            }
        }

        $latestFinanceReject = $document->decisions()
            ->where('decision_type', DecisionTypes::FINANCE_REJECT)
            ->latest('created_at')
            ->first();

        if ($latestFinanceReject) {
            $this->financeRejectReason = is_string($latestFinanceReject->reason ?? null) ? $latestFinanceReject->reason : null;

            $reasons = DecisionItemReason::query()
                ->where('document_decision_id', $latestFinanceReject->id)
                ->with('documentItem')
                ->get();

            $this->financeItemReasons = [];
            foreach ($reasons as $r) {
                $reason = trim((string) ($r->reason ?? ''));
                if ($reason === '') {
                    continue;
                }

                $this->financeItemReasons[] = [
                    'item_name' => (string) ($r->documentItem?->nama_barang ?? ''),
                    'reason' => $reason,
                ];
            }
        }
    }

    public function openApprove(): void
    {
        $this->resetErrorBag();
        $this->showApproveModal = true;
    }

    public function openReject(): void
    {
        $this->resetErrorBag();
        $this->showRejectModal = true;
    }

    public function previewPhoto(string $photoId): void
    {
        $this->resetErrorBag();
        $this->previewPhotoId = $photoId;
        $this->showPhotoModal = true;
    }

    public function approve(SpvWorkflowService $workflow): void
    {
        $this->resetErrorBag();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::SPV) {
            abort(403);
        }

        try {
            $workflow->approve($this->document, $actor);
            $this->redirectRoute('spv.history', navigate: true);
        } catch (\Throwable $e) {
            $this->addError('approve', $e->getMessage());
        }
    }

    public function reject(SpvWorkflowService $workflow): void
    {
        $this->resetErrorBag();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::SPV) {
            abort(403);
        }

        if (trim($this->rejectReason) === '') {
            $this->addError('rejectReason', 'Alasan wajib diisi.');
            return;
        }

        try {
            $workflow->reject($this->document, $actor, $this->rejectReason, $this->itemReasons);
            $this->redirectRoute('spv.non-valid', navigate: true);
        } catch (\Throwable $e) {
            $this->addError('reject', $e->getMessage());
        }
    }

    public function canApprove(): bool
    {
        $doc = $this->document;
        if (! in_array($doc->status, [DocumentStatuses::WAREHOUSE_SUBMITTED, DocumentStatuses::FINANCE_REJECTED], true)) {
            return false;
        }

        foreach ($doc->items as $item) {
            if ($item->match_status !== ItemMatchStatuses::SESUAI) {
                return false;
            }
            if ($item->photos->count() < 1) {
                return false;
            }
        }

        return true;
    }

    public function canReject(): bool
    {
        return in_array($this->document->status, [DocumentStatuses::WAREHOUSE_SUBMITTED, DocumentStatuses::FINANCE_REJECTED], true);
    }

    public function render()
    {
        $doc = $this->document->load(['items.photos']);

        return view('livewire.spv.documents.show-page', [
            'document' => $doc,
            'items' => $doc->items,
            'canApprove' => $this->canApprove(),
            'canReject' => $this->canReject(),
            'financeRejectReason' => $this->financeRejectReason,
            'financeItemReasons' => $this->financeItemReasons,
        ])
            ->layoutData([
                'title' => 'SPV Document Detail',
                'pageTitle' => 'Detail',
            ]);
    }
}
