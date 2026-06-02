<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function warehouseSubmissions()
    {
        return $this->hasMany(Document::class, 'warehouse_submitted_by');
    }

    public function spvProcessedDocuments()
    {
        return $this->hasMany(Document::class, 'spv_processed_by');
    }

    public function financeProcessedDocuments()
    {
        return $this->hasMany(Document::class, 'finance_processed_by');
    }

    public function adminOverriddenDocuments()
    {
        return $this->hasMany(Document::class, 'admin_overridden_by');
    }

    public function uploadedItemPhotos()
    {
        return $this->hasMany(ItemPhoto::class, 'uploaded_by');
    }

    public function documentDecisions()
    {
        return $this->hasMany(DocumentDecision::class, 'actor_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }
}
