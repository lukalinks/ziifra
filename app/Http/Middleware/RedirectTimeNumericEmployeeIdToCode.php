<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectTimeNumericEmployeeIdToCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        if (! $request->routeIs('time.index', 'time.export', 'time.create')) {
            return $next($request);
        }

        $employeeId = $request->integer('employee_id');

        if ($employeeId <= 0) {
            return $next($request);
        }

        $employee = Employee::query()->find($employeeId);

        if ($employee === null || blank($employee->employee_code)) {
            return $next($request);
        }

        $query = $request->query();
        unset($query['employee_id']);
        $query['employee'] = $employee->employee_code;

        return redirect()->to($request->url().'?'.http_build_query($query), 301);
    }
}
