<?php

namespace App\Services;

use App\Enums\EmployeeDocumentType;
use App\Models\DocumentFolder;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayrollTimeArchiveService
{
    public const FOLDER_NAME = 'Payroll';

    public function __construct(
        protected PayrollTimeExportService $exports,
        protected PayrollTimeService $payrollTime,
    ) {}

    public function payrollFolder(Organization $organization): DocumentFolder
    {
        return DocumentFolder::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => self::FOLDER_NAME,
            ],
            ['name' => self::FOLDER_NAME],
        );
    }

    public function archiveMonth(
        Organization $organization,
        int $year,
        int $month,
        ?int $projectId,
        User $uploadedBy,
    ): void {
        $folder = $this->payrollFolder($organization);
        $period = Carbon::create($year, $month, 1);

        $this->archivePdf($organization, $year, $month, $projectId, $folder, $uploadedBy, $period);
        $this->archiveCsv($organization, $year, $month, $projectId, $folder, $uploadedBy, $period);
    }

    /**
     * Archive each completed month in the given year (excludes current and future months).
     */
    public function archivePastMonthsInYear(
        Organization $organization,
        int $year,
        User $uploadedBy,
        ?int $projectId = null,
    ): int {
        $archived = 0;
        $limit = $year === (int) now()->year ? (int) now()->month - 1 : 12;

        for ($month = 1; $month <= max(0, $limit); $month++) {
            $this->archiveMonth($organization, $year, $month, $projectId, $uploadedBy);
            $archived++;
        }

        return $archived;
    }

    protected function archivePdf(
        Organization $organization,
        int $year,
        int $month,
        ?int $projectId,
        DocumentFolder $folder,
        User $uploadedBy,
        Carbon $period,
    ): void {
        $data = $this->exports->exportData($organization, $year, $month, $projectId);
        $pdf = Pdf::loadView('app.payroll-time.export-pdf', $data)->output();
        $filename = $this->monthFilename($organization, $year, $month, 'pdf');
        $title = $this->monthTitle($period, $data['project']?->name);

        $this->upsertFolderDocument($organization, $folder, $uploadedBy, $title, $filename, $pdf);
    }

    protected function archiveCsv(
        Organization $organization,
        int $year,
        int $month,
        ?int $projectId,
        DocumentFolder $folder,
        User $uploadedBy,
        Carbon $period,
    ): void {
        $data = $this->exports->exportData($organization, $year, $month, $projectId);
        $csv = $this->exports->csvContent($data['rows'], $data['totals']);
        $filename = $this->monthFilename($organization, $year, $month, 'csv');
        $title = $this->monthTitle($period, $data['project']?->name).' (Excel)';

        $this->upsertFolderDocument($organization, $folder, $uploadedBy, $title, $filename, $csv);
    }

    protected function upsertFolderDocument(
        Organization $organization,
        DocumentFolder $folder,
        User $uploadedBy,
        string $title,
        string $originalFilename,
        string $binary,
    ): EmployeeDocument {
        $existing = EmployeeDocument::query()
            ->where('organization_id', $organization->id)
            ->where('document_folder_id', $folder->id)
            ->where('title', $title)
            ->first();

        if ($existing !== null) {
            Storage::disk('local')->delete($existing->file_path);
            $existing->delete();
        }

        $directory = sprintf('organizations/%d/documents/payroll', $organization->id);
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'bin';
        $path = $directory.'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk('local')->put($path, $binary);

        return EmployeeDocument::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => null,
            'uploaded_by_user_id' => $uploadedBy->id,
            'document_folder_id' => $folder->id,
            'type' => EmployeeDocumentType::Other,
            'title' => $title,
            'file_path' => $path,
            'original_filename' => $originalFilename,
        ]);
    }

    protected function monthTitle(Carbon $period, ?string $projectName): string
    {
        $label = $period->format('F Y');

        if ($projectName) {
            return "Payroll {$label} — {$projectName}";
        }

        return "Payroll {$label}";
    }

    protected function monthFilename(Organization $organization, int $year, int $month, string $ext): string
    {
        return sprintf('payroll-%s-%04d-%02d.%s', $organization->slug, $year, $month, $ext);
    }
}
