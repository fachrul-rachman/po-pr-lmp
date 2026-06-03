<?php

namespace App\Models;

use App\Support\Enums\DocumentStatuses;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'accurate_synced_at' => 'datetime',
            'warehouse_submitted_at' => 'datetime',
            'spv_processed_at' => 'datetime',
            'finance_processed_at' => 'datetime',
            'admin_overridden_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function decisions()
    {
        return $this->hasMany(DocumentDecision::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function warehouseSubmittedBy()
    {
        return $this->belongsTo(User::class, 'warehouse_submitted_by');
    }

    public function spvProcessedBy()
    {
        return $this->belongsTo(User::class, 'spv_processed_by');
    }

    public function financeProcessedBy()
    {
        return $this->belongsTo(User::class, 'finance_processed_by');
    }

    public function adminOverriddenBy()
    {
        return $this->belongsTo(User::class, 'admin_overridden_by');
    }

    public function isEditableByWarehouse(): bool
    {
        return in_array($this->status, [
            null,
            DocumentStatuses::WAREHOUSE_SUBMITTED,
            DocumentStatuses::SPV_REJECTED,
        ], true);
    }
}
