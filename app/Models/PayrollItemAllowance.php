<?php

namespace App\Models;

use App\Enums\AllowanceTaxTreatment;
use App\Enums\PayrollAllowanceKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemAllowance extends Model
{
    protected $fillable = [
        'payroll_item_id',
        'label',
        'amount',
        'tax_treatment',
        'kind',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_treatment' => AllowanceTaxTreatment::class,
            'kind' => PayrollAllowanceKind::class,
            'sort_order' => 'integer',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
