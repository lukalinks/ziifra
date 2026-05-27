<?php

namespace App\Http\Middleware;

use App\Models\PayrollRun;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPayrollNumericUrlToPeriod
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $route = $request->route();

        if ($route !== null && $route->hasParameter('payrollRun')) {
            $run = $route->parameter('payrollRun');

            if ($run instanceof PayrollRun) {
                $segments = $request->segments();
                $index = array_search('payroll', $segments, true);
                $raw = $index !== false ? ($segments[$index + 1] ?? null) : null;
                $periodSlug = $run->periodSlug();

                if (is_string($raw) && ctype_digit($raw) && $raw !== $periodSlug) {
                    $routeName = $route->getName();

                    if ($routeName !== null) {
                        $parameters = $route->parameters();
                        $parameters['payrollRun'] = $run;

                        return redirect()->route($routeName, $parameters, 301);
                    }
                }
            }
        }

        return $next($request);
    }
}
