<?php

namespace App\Http\Middleware;

use App\Services\LocaleConfigurationService;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    public function __construct(
        protected LocaleConfigurationService $locales,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $preferred = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? CurrentOrganization::get()?->locale;

        if ($preferred === null) {
            $preferred = $this->locales->defaultCode();
        }

        app()->setLocale($this->locales->resolve($preferred));

        return $next($request);
    }
}
