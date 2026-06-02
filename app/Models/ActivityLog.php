<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'document_id',
        'previous_status',
        'new_status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Activity logs must be append-only (no edit/delete through normal flow).
        static::updating(function () {
            throw new RuntimeException('Activity logs are immutable.');
        });

        static::deleting(function () {
            throw new RuntimeException('Activity logs cannot be deleted.');
        });
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function scopeForActorRole($query, string $role)
    {
        return $query->where('actor_role', $role);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForDocumentNumberLike($query, string $term)
    {
        return $query->whereHas('document', function ($q) use ($term) {
            $q->where('document_number', 'like', '%'.$term.'%');
        });
    }
}
