<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\Invoice;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_view_and_mark_invoice_paid(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('invoices.store', $result['organization']), [
                'client_name' => 'Client SHPK',
                'client_email' => 'billing@client.test',
                'title' => 'Consulting March',
                'amount' => 1000,
                'tax_percent' => 18,
                'issue_date' => '2026-03-01',
                'due_date' => '2026-03-31',
            ])
            ->assertRedirect();

        $invoice = Invoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertStringStartsWith('INV-'.now()->year.'-', $invoice->invoice_number);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('invoices.send', $result['organization'], ['invoice' => $invoice]))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('invoices.mark-paid', $result['organization'], ['invoice' => $invoice]))
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }

    public function test_employee_cannot_access_invoices(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('invoices.index', $result['organization']))
            ->assertForbidden();
    }

    public function test_overdue_filter_lists_sent_past_due_invoices(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        Invoice::query()->create([
            'organization_id' => $result['organization']->id,
            'created_by_user_id' => $result['user']->id,
            'invoice_number' => 'INV-2026-0001',
            'client_name' => 'Late Client',
            'title' => 'Overdue bill',
            'amount' => 500,
            'tax_percent' => 0,
            'currency' => 'EUR',
            'issue_date' => now()->subMonth(),
            'due_date' => now()->subWeek(),
            'status' => InvoiceStatus::Sent,
            'sent_at' => now()->subMonth(),
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('invoices.index', $result['organization'], ['status' => 'overdue']))
            ->assertOk()
            ->assertSee('Late Client', false);
    }
}
