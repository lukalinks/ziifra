<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseClaimStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasWorkspaceRoutes;
use App\Support\ExpenseReceiptStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaim extends Model
{
    use BelongsToOrganization, HasWorkspaceRoutes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'submitted_by_user_id',
        'category',
        'title',
        'amount',
        'currency',
        'expense_date',
        'status',
        'receipt_path',
        'original_filename',
        'notes',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'status' => ExpenseClaimStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ExpenseClaim $claim): void {
            ExpenseReceiptStorage::delete($claim->receipt_path);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === ExpenseClaimStatus::Pending;
    }

    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format((float) $this->amount, 2);
    }

    public function hasReceipt(): bool
    {
        return ExpenseReceiptStorage::exists($this->receipt_path);
    }
}
