<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecisionItemReason extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function decision()
    {
        return $this->belongsTo(DocumentDecision::class, 'document_decision_id');
    }

    public function documentItem()
    {
        return $this->belongsTo(DocumentItem::class, 'document_item_id');
    }
}

