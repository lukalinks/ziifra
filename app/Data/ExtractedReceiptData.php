<?php

namespace App\Data;

use App\Enums\ExpenseCategory;

final class ExtractedReceiptData
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?float $amount = null,
        public readonly ?string $expenseDate = null,
        public readonly ?ExpenseCategory $category = null,
        public readonly ?string $notes = null,
    ) {}

    public function hasAnyField(): bool
    {
        return $this->title !== null
            || $this->amount !== null
            || $this->expenseDate !== null
            || $this->category !== null
            || $this->notes !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'amount' => $this->amount,
            'expense_date' => $this->expenseDate,
            'category' => $this->category?->value,
            'notes' => $this->notes,
        ], fn (mixed $value) => $value !== null && $value !== '');
    }
}
