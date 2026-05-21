<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditService;
use App\Services\LocaleConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageSettingsController extends Controller
{
    public function edit(LocaleConfigurationService $locales): View
    {
        return view('admin.languages.edit', [
            'definitions' => $locales->definitions(),
            'enabled' => $locales->enabledCodes(),
            'default' => $locales->defaultCode(),
        ]);
    }

    public function update(
        Request $request,
        LocaleConfigurationService $locales,
        AdminAuditService $audit,
    ): RedirectResponse {
        $validated = $request->validate([
            'enabled' => ['required', 'array', 'min:1'],
            'enabled.*' => ['required', 'string', Rule::in($locales->allCodes())],
            'default' => [
                'required',
                'string',
                Rule::in($locales->allCodes()),
                Rule::in($request->input('enabled', [])),
            ],
        ]);

        $locales->update($validated['enabled'], $validated['default'], $request->user());

        $audit->log(
            $request->user(),
            'platform.languages_updated',
            metadata: [
                'enabled' => $validated['enabled'],
                'default' => $validated['default'],
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.languages.edit')
            ->with('status', __('admin.languages.saved'));
    }
}
