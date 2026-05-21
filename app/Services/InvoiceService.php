<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;

class InvoiceService
{
    /**
     * @param  array{
     *     client_name: string,
     *     client_email?: string|null,
     *     title: string,
     *     amount: float|string,
     *     tax_percent?: float|string,
     *     issue_date: string,
     *     due_date: string,
     *     notes?: string|null,
     * }  $data
     */
    public function create(array $data, User $user): Invoice
    {
        $organization = CurrentOrganization::check();

        return Invoice::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $user->id,
            'invoice_number' => $this->nextInvoiceNumber($organization),
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'] ?? null,
            'title' => $data['title'],
            'amount' => $data['amount'],
            'tax_percent' => $data['tax_percent'] ?? 0,
            'currency' => $organization->currency ?? 'EUR',
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'status' => InvoiceStatus::Draft,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  array{
     *     client_name: string,
     *     client_email?: string|null,
     *     title: string,
     *     amount: float|string,
     *     tax_percent?: float|string,
     *     issue_date: string,
     *     due_date: string,
     *     notes?: string|null,
     * }  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update([
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'] ?? null,
            'title' => $data['title'],
            'amount' => $data['amount'],
            'tax_percent' => $data['tax_percent'] ?? 0,
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $invoice->fresh();
    }

    public function markSent(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function cancel(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::Cancelled,
        ]);

        return $invoice->fresh();
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->delete();
    }

    public function nextInvoiceNumber(Organization $organization): string
    {
        $year = now()->year;
        $prefix = 'INV-'.$year.'-';

        $latest = Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;

        if ($latest !== null && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
