<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{{ __('employees.export_pdf_title') }}</title></head>
<body style="font-family: DejaVu Sans, sans-serif; font-size: 11px;">
    <h1>{{ __('employees.export_pdf_heading', ['organization' => $organization->name]) }}</h1>
    <table width="100%" cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th>{{ __('employees.export_pdf_code') }}</th>
                <th>{{ __('employees.export_pdf_name') }}</th>
                <th>{{ __('employees.export_pdf_email') }}</th>
                <th>{{ __('employees.export_pdf_department') }}</th>
                <th>{{ __('employees.export_pdf_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $employee)
                <tr>
                    <td>{{ $employee->displayCode() }}</td>
                    <td>{{ $employee->fullName() }}</td>
                    <td>{{ $employee->email }}</td>
                    <td>{{ $employee->department?->name }}</td>
                    <td>{{ $employee->employment_status?->label() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
