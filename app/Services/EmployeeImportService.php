<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EmployeeImportService
{
    /** @var list<string> */
    public const TEMPLATE_HEADERS = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'position',
        'manager_email',
        'employment_type',
        'employment_status',
        'start_date',
    ];

    public function __construct(
        protected OrganizationBillingService $billing,
        protected BillingNotificationService $billingNotifications,
    ) {}

    /**
     * @return array{imported: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function import(Organization $organization, UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $seenEmails = [];

        $departments = Department::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy(fn (Department $d) => Str::lower($d->name));

        $positions = Position::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy(fn (Position $p) => Str::lower($p->title));

        $managersByEmail = Employee::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->get(['id', 'email'])
            ->mapWithKeys(fn (Employee $e) => [Str::lower((string) $e->email) => $e->id]);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isBlankRow($row)) {
                continue;
            }

            if (! $this->billing->canAddEmployee($organization)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => __('import.limit_reached'),
                ];
                $skipped += count($rows) - $index;

                break;
            }

            $normalized = $this->normalizeRow($row);

            $validationError = $this->validateRow(
                $organization,
                $normalized,
                $seenEmails,
                $departments,
                $positions,
                $managersByEmail,
            );

            if ($validationError !== null) {
                $errors[] = ['row' => $rowNumber, 'message' => $validationError];
                $skipped++;

                continue;
            }

            $email = $normalized['email'] !== '' ? $normalized['email'] : null;

            if ($email !== null) {
                $seenEmails[] = Str::lower($email);
            }

            Employee::query()->create([
                'organization_id' => $organization->id,
                'first_name' => $normalized['first_name'],
                'last_name' => $normalized['last_name'],
                'email' => $email,
                'phone' => $normalized['phone'] ?: null,
                'department_id' => $normalized['department_id'],
                'position_id' => $normalized['position_id'],
                'manager_id' => $normalized['manager_id'],
                'employment_type' => $normalized['employment_type'],
                'employment_status' => $normalized['employment_status'],
                'start_date' => $normalized['start_date'],
            ]);

            $imported++;
            $organization->refresh();
        }

        if ($imported > 0) {
            $this->billingNotifications->notifyEmployeeLimitApproaching($organization->fresh());
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function templateCsv(): string
    {
        $lines = [
            implode(',', self::TEMPLATE_HEADERS),
            'Arben,Krasniqi,arben@acme.test,+38344111222,Engineering,Software Developer,,full_time,active,2026-01-15',
            'Lira,Gashi,lira@acme.test,,Engineering,Product Manager,arben@acme.test,full_time,active,',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * @return list<array<string, string>>
     */
    protected function parseCsv(UploadedFile $file): array
    {
        $contents = $file->get();

        if (! is_string($contents) || trim($contents) === '') {
            return [];
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $stream = fopen('php://memory', 'r+');

        if ($stream === false) {
            return [];
        }

        fwrite($stream, $contents);
        rewind($stream);

        $header = fgetcsv($stream);

        if ($header === false) {
            fclose($stream);

            return [];
        }

        $header = array_map(fn ($h) => Str::lower(trim((string) $h)), $header);

        if (! in_array('first_name', $header, true) || ! in_array('last_name', $header, true)) {
            fclose($stream);

            throw new \InvalidArgumentException(__('import.errors.missing_columns'));
        }

        $rows = [];

        while (($data = fgetcsv($stream)) !== false) {
            $row = [];

            foreach ($header as $i => $key) {
                $row[$key] = trim((string) ($data[$i] ?? ''));
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function isBlankRow(array $row): bool
    {
        return trim(implode('', $row)) === '';
    }

    /**
     * @param  array<string, string>  $row
     * @return array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string,
     *     department_id: ?int,
     *     position_id: ?int,
     *     manager_id: ?int,
     *     employment_type: EmploymentType,
     *     employment_status: EmploymentStatus,
     *     start_date: ?Carbon,
     * }
     */
    protected function normalizeRow(array $row): array
    {
        return [
            'first_name' => trim($row['first_name'] ?? ''),
            'last_name' => trim($row['last_name'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'phone' => trim($row['phone'] ?? ''),
            'department' => trim($row['department'] ?? ''),
            'position' => trim($row['position'] ?? ''),
            'manager_email' => trim($row['manager_email'] ?? ''),
            'employment_type' => trim($row['employment_type'] ?? ''),
            'employment_status' => trim($row['employment_status'] ?? ''),
            'start_date' => trim($row['start_date'] ?? ''),
            'department_id' => null,
            'position_id' => null,
            'manager_id' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $seenEmails
     * @param  \Illuminate\Support\Collection<string, Department>  $departments
     * @param  \Illuminate\Support\Collection<string, Position>  $positions
     * @param  \Illuminate\Support\Collection<string, int>  $managersByEmail
     */
    protected function validateRow(
        Organization $organization,
        array &$row,
        array $seenEmails,
        $departments,
        $positions,
        $managersByEmail,
    ): ?string {
        if ($row['first_name'] === '' || $row['last_name'] === '') {
            return __('import.errors.name_required');
        }

        if ($row['email'] !== '') {
            if (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                return __('import.errors.invalid_email');
            }

            $lower = Str::lower($row['email']);

            if (in_array($lower, $seenEmails, true)) {
                return __('import.errors.duplicate_email_in_file');
            }

            if (Employee::query()
                ->where('organization_id', $organization->id)
                ->whereRaw('LOWER(email) = ?', [$lower])
                ->exists()) {
                return __('import.errors.email_exists');
            }
        }

        if ($row['department'] !== '') {
            $department = $departments->get(Str::lower($row['department']));

            if ($department === null) {
                return __('import.errors.unknown_department', ['name' => $row['department']]);
            }

            $row['department_id'] = $department->id;
        }

        if ($row['position'] !== '') {
            $position = $positions->get(Str::lower($row['position']));

            if ($position === null) {
                return __('import.errors.unknown_position', ['name' => $row['position']]);
            }

            $row['position_id'] = $position->id;
        }

        if ($row['manager_email'] !== '') {
            $managerId = $managersByEmail->get(Str::lower($row['manager_email']));

            if ($managerId === null) {
                return __('import.errors.unknown_manager', ['email' => $row['manager_email']]);
            }

            $row['manager_id'] = $managerId;
        }

        $defaultType = $organization->default_employment_type instanceof EmploymentType
            ? $organization->default_employment_type
            : (EmploymentType::tryFrom((string) ($organization->default_employment_type ?? '')) ?? EmploymentType::FullTime);

        $typeValue = $row['employment_type'] !== '' ? $row['employment_type'] : $defaultType->value;
        $type = EmploymentType::tryFrom($typeValue);

        if ($type === null) {
            return __('import.errors.invalid_type');
        }

        $statusValue = $row['employment_status'] !== '' ? $row['employment_status'] : EmploymentStatus::Active->value;
        $status = EmploymentStatus::tryFrom($statusValue);

        if ($status === null) {
            return __('import.errors.invalid_status');
        }

        $startDate = null;

        if ($row['start_date'] !== '') {
            try {
                $startDate = Carbon::parse($row['start_date'])->startOfDay();
            } catch (\Throwable) {
                return __('import.errors.invalid_date');
            }
        }

        $row['employment_type'] = $type;
        $row['employment_status'] = $status;
        $row['start_date'] = $startDate;

        return null;
    }
}
