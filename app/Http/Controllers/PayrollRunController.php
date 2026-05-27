<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PayrollRun;
use App\Http\Requests\UpdatePayrollDraftRequest;
use App\Enums\PayrollGenerationMode;
use App\Services\HourlyPayrollService;
use App\Services\PayrollRunService;
use App\Services\PayslipExportService;
use App\Services\PayslipEmailService;
use App\Services\PayslipPdfService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PayrollRunController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', PayrollRun::class);

        $runs = PayrollRun::query()
            ->withCount('items')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12);

        return view('app.payroll.index', [
            'organization' => CurrentOrganization::check(),
            'runs' => $runs,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PayrollRun::class);

        $year = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);

        return view('app.payroll.create', [
            'organization' => CurrentOrganization::check(),
            'defaultYear' => $year,
            'defaultMonth' => $month,
            'employees' => \App\Models\Employee::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'generationModes' => PayrollGenerationMode::cases(),
        ]);
    }

    public function store(Request $request, PayrollRunService $payroll, HourlyPayrollService $hourlyPayroll): RedirectResponse
    {
        $this->authorize('create', PayrollRun::class);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'calculation_mode' => ['nullable', 'in:salary,hourly'],
            'generation_mode' => ['nullable', 'in:all,individual,group'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $mode = PayrollGenerationMode::tryFrom($validated['generation_mode'] ?? 'all') ?? PayrollGenerationMode::All;
        $employeeIds = null;

        if ($mode === PayrollGenerationMode::Individual && ! empty($validated['employee_id'])) {
            $employeeIds = [(int) $validated['employee_id']];
        } elseif ($mode === PayrollGenerationMode::Group) {
            $employeeIds = array_map('intval', $validated['employee_ids'] ?? []);
        }

        if (($validated['calculation_mode'] ?? 'salary') === 'hourly') {
            $run = $hourlyPayroll->create(
                CurrentOrganization::check(),
                (int) $validated['year'],
                (int) $validated['month'],
                $mode,
                $employeeIds,
            );
        } else {
            $run = $payroll->create(
                CurrentOrganization::check(),
                (int) $validated['year'],
                (int) $validated['month'],
            );
        }

    return redirect()
            ->to($run->showUrl())
            ->with('alert', [
                'variant' => 'success',
                'title' => __('payroll.flash.created_title'),
                'body' => __('payroll.flash.created_body'),
            ]);
    }

    public function show(Organization $organization, PayrollRun $payrollRun): View
    {
        $this->authorize('view', $payrollRun);

        $payrollRun->load(['items.employee', 'items.allowanceLines', 'lockedBy']);

        $totals = [
            'base_gross' => $payrollRun->items->sum('base_gross_salary'),
            'allowances' => $payrollRun->items->sum('allowances'),
            'exempt_allowances' => $payrollRun->items->sum('exempt_allowances_total'),
            'gross' => $payrollRun->items->sum('gross_salary'),
            'employee_pension' => $payrollRun->items->sum('employee_pension'),
            'employer_pension' => $payrollRun->items->sum('employer_pension'),
            'income_tax' => $payrollRun->items->sum('income_tax'),
            'net' => $payrollRun->items->sum('net_salary'),
        ];

        return view('app.payroll.show', [
            'organization' => CurrentOrganization::check(),
            'run' => $payrollRun,
            'totals' => $totals,
        ]);
    }

    public function update(UpdatePayrollDraftRequest $request, Organization $organization, PayrollRun $payrollRun, PayrollRunService $payroll): RedirectResponse
    {
        $this->authorize('update', $payrollRun);

        $items = $request->validated('items') ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        $payroll->updateDraftItems($payrollRun, $items);

        return redirect()
            ->to($payrollRun->showUrl())
            ->with('alert', [
                'variant' => 'success',
                'title' => __('payroll.flash.updated_title'),
                'body' => __('payroll.flash.updated_body'),
            ]);
    }

    public function lock(Request $request, Organization $organization, PayrollRun $payrollRun, PayrollRunService $payroll): RedirectResponse
    {
        $this->authorize('lock', $payrollRun);

        $payroll->lock($payrollRun, $request->user());

        return redirect()
            ->to($payrollRun->showUrl())
            ->with('alert', [
                'variant' => 'success',
                'title' => __('payroll.flash.locked_title'),
                'body' => __('payroll.flash.locked_body'),
            ]);
    }

    public function exportPdfs(
        Organization $organization,
        PayrollRun $payrollRun,
        PayslipPdfService $payslipPdf,
    ): BinaryFileResponse {
        $this->authorize('view', $payrollRun);

        $organization = CurrentOrganization::check();
        $payrollRun->load('items');

        abort_if($payrollRun->items->isEmpty(), 404);

        $tmpBase = tempnam(sys_get_temp_dir(), 'ziifra_payslips_');
        if ($tmpBase === false) {
            abort(500);
        }

        @unlink($tmpBase);

        $zip = new ZipArchive;
        if ($zip->open($tmpBase, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500);
        }

        foreach ($payrollRun->items as $item) {
            $binary = $payslipPdf->makePdf($organization, $payrollRun, $item)->output();
            $zip->addFromString($payslipPdf->zipEntryFilename($payrollRun, $item), $binary);
        }

        $zip->close();

        $downloadName = sprintf(
            'payslips-%s-%d-%02d.zip',
            $organization->slug,
            $payrollRun->year,
            $payrollRun->month,
        );

        return response()->download($tmpBase, $downloadName)->deleteFileAfterSend(true);
    }

    public function exportCsv(Organization $organization, PayrollRun $payrollRun, PayslipExportService $export)
    {
        $this->authorize('view', $payrollRun);

        return $export->exportRunCsv($payrollRun);
    }

    public function emailPayslips(Request $request, Organization $organization, PayrollRun $payrollRun, PayslipEmailService $payslipEmail): RedirectResponse
    {
        $this->authorize('sendPayslipEmails', $payrollRun);

        $result = $payslipEmail->sendForRun($payrollRun, $request->user());

        if ($result['sent'] === 0 && $result['skipped'] > 0 && $result['failed'] === 0) {
            return redirect()
                ->to($payrollRun->showUrl())
                ->with('alert', [
                    'variant' => 'warning',
                    'title' => __('payroll.flash.email_none_title'),
                    'body' => __('payroll.email_bulk_none_sent', ['skipped' => $result['skipped']]),
                ]);
        }

        if ($result['sent'] === 0 && $result['failed'] > 0) {
            return redirect()
                ->to($payrollRun->showUrl())
                ->with('alert', [
                    'variant' => 'danger',
                    'title' => __('payroll.flash.payslip_email_failed_title'),
                    'body' => __('payroll.email_bulk_all_failed', ['failed' => $result['failed']]),
                ]);
        }

        $body = $result['failed'] > 0
            ? __('payroll.email_bulk_result_with_failures', [
                'sent' => $result['sent'],
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ])
            : __('payroll.email_bulk_result', [
                'sent' => $result['sent'],
                'skipped' => $result['skipped'],
            ]);

        $variant = $result['failed'] > 0 ? 'warning' : 'success';

        return redirect()
            ->to($payrollRun->showUrl())
            ->with('alert', [
                'variant' => $variant,
                'title' => __('payroll.flash.email_bulk_title'),
                'body' => $body,
            ]);
    }
}
