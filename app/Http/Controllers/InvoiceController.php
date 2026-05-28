<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Project;
use App\Services\InvoiceExportService;
use App\Services\InvoiceFromHoursService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->orderByDesc('issue_date')->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            if ($status === 'overdue') {
                $query->where('status', InvoiceStatus::Sent)
                    ->whereDate('due_date', '<', now());
            } elseif (in_array($status, array_column(InvoiceStatus::cases(), 'value'), true)) {
                $query->where('status', $status);
            }
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        return view('app.invoices.index', [
            'organization' => CurrentOrganization::check(),
            'invoices' => $query->paginate(20)->withQueryString(),
            'statuses' => InvoiceStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        $organization = CurrentOrganization::check();

        return view('app.invoices.create', [
            'organization' => $organization,
            'invoiceSettings' => $organization->resolvedInvoiceSettings(),
            'projects' => Project::query()->orderBy('name')->get(),
            'prefillProjectId' => request()->integer('project') ?: null,
            'prefillPeriodStart' => request('period_start'),
            'prefillPeriodEnd' => request('period_end'),
        ]);
    }

    public function storeFromHours(Request $request, InvoiceFromHoursService $fromHours): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'client_name' => ['nullable', 'string', 'max:255'],
        ]);

        $project = Project::query()->findOrFail($validated['project_id']);

        $invoice = $fromHours->createFromProjectHours(
            CurrentOrganization::check(),
            $project,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $request->user(),
            $validated['client_name'] ?? null,
        );

        return redirect()
            ->to($invoice->workspaceRoute('invoices.show'))
            ->with('status', __('invoices.created_from_hours'));
    }

    public function pdf(Organization $organization, Invoice $invoice, InvoicePdfService $pdf)
    {
        $this->authorize('view', $invoice);

        return $pdf->download($invoice, $organization);
    }

    public function export(Organization $organization, Invoice $invoice, InvoiceExportService $export)
    {
        $this->authorize('view', $invoice);

        return $export->exportCsv($invoice);
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $invoices): RedirectResponse
    {
        $invoice = $invoices->create($request->validated(), $request->user());

        return redirect()
            ->to($invoice->workspaceRoute('invoices.show'))
            ->with('status', __('invoices.created'));
    }

    public function show(Organization $organization, Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load('createdBy');

        return view('app.invoices.show', [
            'organization' => CurrentOrganization::check(),
            'invoice' => $invoice,
        ]);
    }

    public function edit(Organization $organization, Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        return view('app.invoices.edit', [
            'organization' => CurrentOrganization::check(),
            'invoiceSettings' => CurrentOrganization::check()->resolvedInvoiceSettings(),
            'invoice' => $invoice,
        ]);
    }

    public function update(
        UpdateInvoiceRequest $request,
        Organization $organization,
        Invoice $invoice,
        InvoiceService $invoices,
    ): RedirectResponse {
        $invoices->update($invoice, $request->validated());

        return redirect()
            ->to($invoice->workspaceRoute('invoices.show'))
            ->with('status', __('invoices.updated'));
    }

    public function destroy(Organization $organization, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoices->delete($invoice);

        return redirect()
            ->route('invoices.index')
            ->with('status', __('invoices.deleted'));
    }

    public function markSent(Organization $organization, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        $this->authorize('markSent', $invoice);

        $invoices->markSent($invoice);

        return redirect()
            ->to($invoice->workspaceRoute('invoices.show'))
            ->with('status', __('invoices.marked_sent'));
    }

    public function markPaid(Organization $organization, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        $this->authorize('markPaid', $invoice);

        $invoices->markPaid($invoice);

        return redirect()
            ->to($invoice->workspaceRoute('invoices.show'))
            ->with('status', __('invoices.marked_paid'));
    }

    public function cancel(Organization $organization, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        $invoices->cancel($invoice);

        return redirect()
            ->to($invoice->workspaceRoute('invoices.show'))
            ->with('status', __('invoices.cancelled'));
    }
}
