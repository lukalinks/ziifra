@php
    $invoice = $invoice ?? null;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="client_name" class="ziifra-label-field">{{ __('invoices.client_name') }}</label>
        <input type="text" id="client_name" name="client_name" value="{{ old('client_name', $invoice?->client_name) }}" required maxlength="255" class="ziifra-input">
        @error('client_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="client_email" class="ziifra-label-field">{{ __('invoices.client_email') }}</label>
        <input type="email" id="client_email" name="client_email" value="{{ old('client_email', $invoice?->client_email) }}" maxlength="255" class="ziifra-input">
        @error('client_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="title" class="ziifra-label-field">{{ __('invoices.description') }}</label>
        <input type="text" id="title" name="title" value="{{ old('title', $invoice?->title) }}" required maxlength="255" class="ziifra-input">
        @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="amount" class="ziifra-label-field">{{ __('invoices.amount') }}</label>
        <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="{{ old('amount', $invoice?->amount) }}" required class="ziifra-input">
        @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="tax_percent" class="ziifra-label-field">{{ __('invoices.tax_percent') }}</label>
        <input type="number" step="0.01" min="0" max="100" id="tax_percent" name="tax_percent" value="{{ old('tax_percent', $invoice?->tax_percent ?? 0) }}" class="ziifra-input">
        @error('tax_percent')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="issue_date" class="ziifra-label-field">{{ __('invoices.issue_date') }}</label>
        <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', $invoice?->issue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="ziifra-input">
        @error('issue_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="due_date" class="ziifra-label-field">{{ __('invoices.due_date') }}</label>
        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? now()->addDays(14)->format('Y-m-d')) }}" required class="ziifra-input">
        @error('due_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="ziifra-label-field">{{ __('invoices.notes') }}</label>
        <textarea id="notes" name="notes" rows="3" maxlength="2000" class="ziifra-input">{{ old('notes', $invoice?->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
