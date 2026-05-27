@php
    $contractTemplateOptions = $contractTemplates->map(fn ($contractTemplate) => [
        'slug' => $contractTemplate->slug,
        'name' => $contractTemplate->name,
        'description' => $contractTemplate->description,
        'downloadUrl' => route('documents.templates.download', $contractTemplate),
        'generateUrl' => route('documents.templates.generate', $contractTemplate),
    ])->values();
@endphp

<section class="ziifra-documents-side-card">
    <div class="ziifra-documents-side-card-head">
        <div>
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('documents.templates.title') }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ __('documents.templates.subtitle') }}</p>
        </div>
    </div>

    <div class="ziifra-documents-side-card-body space-y-4">
        @if ($canManageOrganization ?? false)
            <a href="{{ route('settings.contract-templates.index') }}" data-page-nav class="text-xs font-medium text-ziifra-accent-deep hover:underline">
                {{ __('documents.templates.settings.manage_link') }}
            </a>
        @endif

        <div>
            <label for="contract_template_select" class="ziifra-documents-label">
                {{ __('documents.templates.select_contract') }}
            </label>
            <select id="contract_template_select"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm text-ziifra-ink">
                @foreach ($contractTemplates as $contractTemplate)
                    <option value="{{ $contractTemplate->slug }}" @selected($contractTemplate->slug === $selectedContractSlug)>
                        {{ $contractTemplate->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="rounded-xl border border-ziifra-line/70 bg-ziifra-cream/25 p-4">
            <p id="contract_template_description" class="text-xs leading-relaxed text-ziifra-muted"></p>

            <a id="contract_template_download" href="#"
                class="mt-3 inline-flex rounded-lg border border-ziifra-line bg-white px-3 py-1.5 text-xs font-medium text-ziifra-ink hover:bg-ziifra-cream">
                {{ __('documents.templates.download_blank') }}
            </a>

            @if ($canManage)
                <form id="contract_generate_form" method="POST" action="#" class="mt-4 space-y-3 border-t border-ziifra-line/60 pt-4">
                    @csrf
                    <div>
                        <label for="contract_template_employee" class="ziifra-documents-label">
                            {{ __('documents.templates.select_employee') }}
                        </label>
                        <select id="contract_template_employee" name="employee_id" required
                            class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
                            <option value="" disabled selected>{{ __('documents.select_employee') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                    {{ $employee->fullName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-start gap-2 text-xs text-ziifra-muted">
                        <input type="checkbox" name="save_to_documents" value="1" class="mt-0.5 rounded border-ziifra-line"
                            @checked(old('save_to_documents'))>
                        {{ __('documents.templates.save_to_documents') }}
                    </label>
                    <button type="submit" class="ziifra-btn-primary w-full !text-sm">
                        {{ __('documents.templates.generate') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>

<script>
    (function () {
        const templates = @json($contractTemplateOptions);
        const select = document.getElementById('contract_template_select');
        const description = document.getElementById('contract_template_description');
        const downloadLink = document.getElementById('contract_template_download');
        const generateForm = document.getElementById('contract_generate_form');

        if (!select || templates.length === 0) {
            return;
        }

        const templateMap = Object.fromEntries(templates.map((template) => [template.slug, template]));

        function applyTemplate(slug) {
            const template = templateMap[slug] ?? templates[0];

            if (!template) {
                return;
            }

            if (description) {
                description.textContent = template.description ?? '';
            }

            if (downloadLink) {
                downloadLink.href = template.downloadUrl;
            }

            if (generateForm) {
                generateForm.action = template.generateUrl;
            }
        }

        select.addEventListener('change', () => applyTemplate(select.value));
        applyTemplate(select.value);
    })();
</script>
