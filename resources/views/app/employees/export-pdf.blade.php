<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Employees</title></head>
<body style="font-family: DejaVu Sans, sans-serif; font-size: 11px;">
    <h1>{{ $organization->name }} — Employees</h1>
    <table width="100%" cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Status</th>
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
