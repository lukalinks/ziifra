<section class="ziifra-documents-side-card">
    <details class="group">
        <summary class="cursor-pointer list-none text-sm font-semibold text-ziifra-ink marker:content-none">
            <span class="flex items-center justify-between gap-2">
                {{ __('navigation.add_category') }}
                <svg class="h-4 w-4 text-ziifra-muted transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </span>
            <p class="mt-1 text-xs font-normal text-ziifra-muted">{{ __('project_documents.nav_category_hint') }}</p>
        </summary>
        <form method="POST" action="{{ route('nav-items.store') }}" class="mt-4 space-y-3 border-t border-ziifra-line/60 pt-4">
            @csrf
            <div>
                <label for="nav_label" class="ziifra-documents-label">{{ __('navigation.custom_label') }}</label>
                <input type="text" id="nav_label" name="label" required maxlength="80" placeholder="{{ __('navigation.custom_label') }}"
                    class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label for="nav_url" class="ziifra-documents-label">{{ __('navigation.custom_url') }}</label>
                <input type="url" id="nav_url" name="url" required maxlength="500" placeholder="https://"
                    class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
            </div>
            <button type="submit" class="ziifra-btn-app-outline w-full !text-sm">{{ __('navigation.add_category') }}</button>
        </form>
    </details>
</section>
