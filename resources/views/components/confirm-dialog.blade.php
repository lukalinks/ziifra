<div id="ziifra-confirm-dialog"
    class="ziifra-confirm-dialog"
    hidden
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ziifra-confirm-title"
    aria-describedby="ziifra-confirm-message"
    data-confirm-default-title="{{ __('common.confirm_title') }}"
    data-confirm-default-accept="{{ __('common.confirm') }}">
    <button type="button" class="ziifra-confirm-dialog__backdrop" data-confirm-backdrop aria-label="{{ __('common.close') }}"></button>
    <div class="ziifra-confirm-dialog__panel">
        <div class="ziifra-confirm-dialog__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 18h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h2 id="ziifra-confirm-title" class="ziifra-confirm-dialog__title" data-confirm-title>{{ __('common.confirm_title') }}</h2>
        <p id="ziifra-confirm-message" class="ziifra-confirm-dialog__message" data-confirm-message></p>
        <div class="ziifra-confirm-dialog__actions">
            <button type="button" class="ziifra-confirm-dialog__cancel" data-confirm-cancel>{{ __('common.cancel') }}</button>
            <button type="button" class="ziifra-confirm-dialog__accept" data-confirm-accept>{{ __('common.confirm') }}</button>
        </div>
    </div>
</div>
