<?php

namespace App\Models;

use App\Enums\EmployeeDocumentType;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\EmployeeDocumentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'uploaded_by_user_id',
        'type',
        'title',
        'file_path',
        'original_filename',
        'expires_at',
        'expiry_reminder_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmployeeDocumentType::class,
            'expires_at' => 'date',
            'expiry_reminder_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (EmployeeDocument $document): void {
            EmployeeDocumentStorage::delete($document->file_path);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if ($this->expires_at === null || $this->isExpired()) {
            return false;
        }

        return $this->expires_at->lte(now()->addDays($withinDays));
    }
}
