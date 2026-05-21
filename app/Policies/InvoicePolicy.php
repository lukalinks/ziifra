<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Support\CurrentOrganization;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageFinance($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->belongsToCurrentOrganization($user, $invoice)
            && $this->canManageFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageFinance($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice) && $invoice->isDraft();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice) && $invoice->isDraft();
    }

    public function markSent(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && in_array($invoice->status, [InvoiceStatus::Sent], true);
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent], true);
    }

    protected function canManageFinance(User $user): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && ($user->roleIn($organization)?->canManageFinance() ?? false);
    }

    protected function belongsToCurrentOrganization(User $user, Invoice $invoice): bool
    {
        $organization = CurrentOrganization::get();

        return $organization !== null
            && $invoice->organization_id === $organization->id
            && $user->belongsToOrganization($organization);
    }
}
