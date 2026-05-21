<?php

namespace App\Http\Controllers;

use App\Services\ReportAuthorizationService;
use App\Services\ReportService;
use App\Support\CurrentOrganization;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(
        Request $request,
        ReportAuthorizationService $reportAuth,
        ReportService $reports,
    ): View {
        $organization = CurrentOrganization::check();

        abort_unless($reportAuth->canView($request->user(), $organization), 403);

        return view('app.reports.index', [
            'organization' => $organization,
            'report' => $reports->compile($request->user(), $organization),
        ]);
    }

    public function export(
        Request $request,
        ReportAuthorizationService $reportAuth,
        ReportService $reports,
    ): Response {
        $organization = CurrentOrganization::check();

        abort_unless($reportAuth->canView($request->user(), $organization), 403);

        $format = $request->query('format', 'csv');

        if ($format === 'csv') {
            $export = $reports->buildCsvExport($request->user(), $organization);

            return response($export['content'], 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
            ]);
        }

        if ($format === 'json') {
            try {
                $export = $reports->buildJsonExport($request->user(), $organization);
            } catch (\JsonException) {
                abort(500);
            }

            return response($export['content'], 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
            ]);
        }

        abort(400);
    }
}
