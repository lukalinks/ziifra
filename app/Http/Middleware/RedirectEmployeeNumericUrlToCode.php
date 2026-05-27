<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectEmployeeNumericUrlToCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $route = $request->route();

        if ($route !== null && $route->hasParameter('employee')) {
            $employee = $route->parameter('employee');

            if ($employee instanceof Employee && filled($employee->employee_code)) {
                $segments = $request->segments();
                $index = array_search('employees', $segments, true);
                $raw = $index !== false ? ($segments[$index + 1] ?? null) : null;

                if (is_string($raw) && ctype_digit($raw) && $raw !== $employee->employee_code) {
                    $routeName = $route->getName();

                    if ($routeName !== null) {
                        $parameters = $route->parameters();
                        $parameters['employee'] = $employee;

                        return redirect()->route($routeName, $parameters, 301);
                    }
                }
            }
        }

        return $next($request);
    }
}
