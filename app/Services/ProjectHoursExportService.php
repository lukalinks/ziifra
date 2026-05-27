<?php

namespace App\Services;

use App\Models\Project;
use App\Support\SpreadsheetExport;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectHoursExportService
{
    public function __construct(
        protected DailyHoursService $hours,
    ) {}

    public function exportCsv(Project $project, Carbon $month): StreamedResponse
    {
        $grid = $this->hours->gridForProject($project, $month);
        $headers = array_merge(
            [__('daily_hours.export_columns.employee'), __('daily_hours.export_columns.code')],
            array_map(fn (int $day) => (string) $day, $grid['days']),
            [__('daily_hours.total')],
        );

        $rows = [];

        foreach ($grid['employees'] as $employee) {
            $row = [$employee->fullName(), $employee->displayCode()];
            $total = 0.0;

            foreach ($grid['days'] as $day) {
                $entry = $grid['grid'][$employee->id][$day] ?? null;
                $value = $entry ? (float) $entry->hours : 0;
                $row[] = $value > 0 ? number_format($value, 2, '.', '') : '';
                $total += $value;
            }

            $row[] = number_format($total, 2, '.', '');
            $rows[] = $row;
        }

        $filename = sprintf('project-hours-%s-%s.csv', $project->id, $grid['month']);

        return SpreadsheetExport::csvDownload($filename, $headers, $rows);
    }
}
