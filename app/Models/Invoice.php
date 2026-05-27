<?php

namespace App\Models;

use App\Enums\InvoiceSource;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasWorkspaceRoutes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use BelongsToOrganization, HasWorkspaceRoutes;

    protected $fillable = [
        'organization_id',
        'project_id',
        'created_by_user_id',
        'invoice_number',
        'client_name',
        'client_email',
        'title',
        'amount',
        'tax_percent',
        'currency',
        'issue_date',
        'due_date',
        'period_start',
        'period_end',
        'status',
        'source',
        'sent_at',
        'paid_at',
        'notes',
        'line_items',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => InvoiceStatus::class,
            'source' => InvoiceSource::class,
            'line_items' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Sent
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function displayStatus(): InvoiceStatus|string
    {
        if ($this->isOverdue()) {
            return 'overdue';
        }

        return $this->status;
    }

    public function displayStatusLabel(): string
    {
        if ($this->isOverdue()) {
            return __('invoices.status_overdue');
        }

        return $this->status->label();
    }

    public function taxAmount(): string
    {
        $tax = (float) $this->amount * ((float) $this->tax_percent / 100);

        return number_format($tax, 2, '.', '');
    }

    public function totalAmount(): string
    {
        $total = (float) $this->amount + (float) $this->taxAmount();

        return number_format($total, 2, '.', '');
    }

    public function formattedTotal(): string
    {
        return $this->currency.' '.$this->totalAmount();
    }
}
