<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectProjectNumericUrlToCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $route = $request->route();

        if ($route !== null && $route->hasParameter('project')) {
            $project = $route->parameter('project');

            if ($project instanceof Project && filled($project->project_code)) {
                $segments = $request->segments();
                $index = array_search('projects', $segments, true);
                $raw = $index !== false ? ($segments[$index + 1] ?? null) : null;

                if (is_string($raw) && ctype_digit($raw) && $raw !== $project->project_code) {
                    $routeName = $route->getName();

                    if ($routeName !== null) {
                        $parameters = $route->parameters();
                        $parameters['project'] = $project;

                        return redirect()->route($routeName, $parameters, 301);
                    }
                }
            }
        }

        return $next($request);
    }
}
