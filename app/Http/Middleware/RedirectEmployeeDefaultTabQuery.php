<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectEmployeeDefaultTabQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        if (! $request->routeIs('employees.show')) {
            return $next($request);
        }

        if ($request->string('tab')->toString() !== 'overview') {
            return $next($request);
        }

        $query = $request->query();
        unset($query['tab']);

        $target = $request->url();

        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        return redirect()->to($target, 301);
    }
}
