<?php

namespace App\Livewire\Admin\Documents;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentDecision;
use App\Services\Accurate\AccurateRefreshService;
use App\Services\Workflow\AdminWorkflowService;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowPage extends Component
{
    public Document $document;

    public bool $showRefreshModal = false;
    public bool $showOverrideModal = false;

    public string $overrideStatus = '';
    public string $overrideReason = '';

    public ?string $noticeSuccess = null;
    public ?string $noticeInfo = null;

    public function mount(Document $document): void
    {
        $this->document = $document;
    }

    public function openRefresh(): void
    {
        $this->resetErrorBag();
        $this->noticeSuccess = null;
        $this->noticeInfo = null;
        $this->showRefreshModal = true;
    }

    public function openOverride(): void
    {
        $this->resetErrorBag();
        $this->noticeSuccess = null;
        $this->noticeInfo = null;
        $this->overrideStatus = $this->document->status ?? '';
        $this->overrideReason = '';
        $this->showOverrideModal = true;
    }

    public function refreshFromAccurate(AccurateRefreshService $refresh): void
    {
        $this->resetErrorBag();
        $this->noticeSuccess = null;
        $this->noticeInfo = null;

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::ADMIN) {
            abort(403);
        }

        $before = $this->document->fresh();
        $fromStatus = $before?->status;

        try {
            $refresh->refresh($this->document);
        } catch (\Throwable $e) {
            app(\App\Services\ActivityLogService::class)->logUserAction(
                actor: $actor,
                action: 'admin_accurate_refresh',
                payload: [
                    'document_id' => $this->document->id,
                    'result' => 'failed',
                    'error' => $e->getMessage(),
                ],
                document: $this->document,
                previousStatus: $fromStatus,
                newStatus: $fromStatus,
            );
            $this->addError('refresh', $e->getMessage());
            return;
        }

        $this->document = $this->document->fresh();

        $latest = ActivityLog::query()
            ->where('document_id', $this->document->id)
            ->whereIn('action', ['accurate_refresh_no_change', 'accurate_refresh_with_change'])
            ->latest('created_at')
            ->first();

        $toStatus = $this->document->status;

        if ($toStatus !== null) {
            DocumentDecision::create([
                'document_id' => $this->document->id,
                'decision_type' => DecisionTypes::ACCURATE_REFRESH,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => null,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
            ]);
        }

        app(\App\Services\ActivityLogService::class)->logUserAction(
            actor: $actor,
            action: 'admin_accurate_refresh',
            payload: [
                'document_id' => $this->document->id,
                'result' => $latest?->action,
            ],
            document: $this->document,
            previousStatus: $fromStatus,
            newStatus: $toStatus,
        );

        if (($latest?->action ?? null) === 'accurate_refresh_no_change') {
            $this->noticeInfo = 'Data Accurate sudah terbaru.';
        } else {
            $this->noticeSuccess = 'Data berhasil diperbarui dari Accurate.';
        }

        $this->showRefreshModal = false;
    }

    public function override(AdminWorkflowService $workflow): void
    {
        $this->resetErrorBag();
        $this->noticeSuccess = null;
        $this->noticeInfo = null;

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::ADMIN) {
            abort(403);
        }

        if (trim($this->overrideStatus) === '') {
            $this->addError('overrideStatus', 'Status wajib dipilih.');
            return;
        }

        if (trim($this->overrideReason) === '') {
            $this->addError('overrideReason', 'Alasan wajib diisi.');
            return;
        }

        try {
            $workflow->overrideStatus($this->document, $actor, $this->overrideStatus, $this->overrideReason);
            $this->document = $this->document->fresh();
            $this->noticeSuccess = 'Status berhasil diubah.';
            $this->showOverrideModal = false;
        } catch (\Throwable $e) {
            $this->addError('override', $e->getMessage());
        }
    }

    public function render()
    {
        $doc = $this->document->load([
            'items.photos',
            'decisions.actor',
            'decisions.itemReasons',
            'activityLogs.actor',
        ]);

        $logs = ActivityLog::query()
            ->where('document_id', $doc->id)
            ->with(['actor'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('livewire.admin.documents.show-page', [
            'document' => $doc,
            'items' => $doc->items,
            'decisions' => $doc->decisions->sortByDesc('created_at')->values(),
            'recentLogs' => $logs,
            'statuses' => DocumentStatuses::all(),
        ])
            ->layoutData([
                'title' => 'Admin Document Detail',
                'pageTitle' => 'Document Detail',
            ]);
    }
}
