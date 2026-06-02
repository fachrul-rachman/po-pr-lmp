<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function photos()
    {
        return $this->hasMany(ItemPhoto::class, 'document_item_id');
    }

    public function decisionItemReasons()
    {
        return $this->hasMany(DecisionItemReason::class, 'document_item_id');
    }
}

