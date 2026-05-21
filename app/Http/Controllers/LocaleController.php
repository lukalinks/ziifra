<?php

namespace App\Http\Controllers;

use App\Services\LocaleConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request, LocaleConfigurationService $locales): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($locales->enabledCodes())],
        ]);

        $locale = $locales->resolve($validated['locale']);

        if ($request->user() !== null) {
            $request->user()->update(['locale' => $locale]);
        }

        $request->session()->put('locale', $locale);

        return back()->with('status', __('locales.switched'));
    }
}
