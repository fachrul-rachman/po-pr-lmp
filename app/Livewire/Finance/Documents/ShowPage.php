<?php

namespace App\Livewire\Finance\Documents;

use App\Models\DecisionItemReason;
use App\Models\Document;
use App\Services\Workflow\FinanceWorkflowService;
use App\Support\Enums\DecisionTypes;
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

    public bool $showCloseModal = false;
    public bool $showRejectModal = false;
    public bool $showPhotoModal = false;

    public ?string $previewPhotoId = null;

    public string $rejectReason = '';

    /** @var array<string, string> */
    public array $itemReasons = [];

    public function mount(Document $document): void
    {
        $this->document = $document;

        // Pre-fill from latest Finance reject decision (if any), as a convenience when reviewing history.
        $latestReject = $document->decisions()
            ->where('decision_type', DecisionTypes::FINANCE_REJECT)
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
    }

    public function openClose(): void
    {
        $this->resetErrorBag();
        $this->showCloseModal = true;
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

    public function close(FinanceWorkflowService $workflow): void
    {
        $this->resetErrorBag();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::FINANCE) {
            abort(403);
        }

        try {
            $workflow->close($this->document, $actor);
            $this->redirectRoute('finance.history', navigate: true);
        } catch (\Throwable $e) {
            $this->addError('close', $e->getMessage());
        }
    }

    public function reject(FinanceWorkflowService $workflow): void
    {
        $this->resetErrorBag();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::FINANCE) {
            abort(403);
        }

        if (trim($this->rejectReason) === '') {
            $this->addError('rejectReason', 'Alasan wajib diisi.');
            return;
        }

        try {
            $workflow->reject($this->document, $actor, $this->rejectReason, $this->itemReasons);
            $this->redirectRoute('finance.history', navigate: true);
        } catch (\Throwable $e) {
            $this->addError('reject', $e->getMessage());
        }
    }

    public function canClose(): bool
    {
        if ($this->document->status !== DocumentStatuses::SPV_APPROVED) {
            return false;
        }

        foreach ($this->document->items as $item) {
            if ($item->match_status === ItemMatchStatuses::TIDAK_SESUAI) {
                return false;
            }
        }

        return true;
    }

    public function canReject(): bool
    {
        return $this->document->status === DocumentStatuses::SPV_APPROVED;
    }

    public function render()
    {
        $doc = $this->document->load([
            'items.photos',
            'spvProcessedBy',
            'financeProcessedBy',
            'decisions.actor',
            'decisions.itemReasons',
        ]);

        return view('livewire.finance.documents.show-page', [
            'document' => $doc,
            'items' => $doc->items,
            'canClose' => $this->canClose(),
            'canReject' => $this->canReject(),
        ])
            ->layoutData([
                'title' => 'Finance Document Detail',
                'pageTitle' => 'Detail',
            ]);
    }
}
