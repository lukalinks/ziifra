@extends('layouts.app')

@section('title', __('expenses.new'))
@section('header', __('expenses.new'))

@section('content')
<form method="POST"
    action="{{ route('expenses.store') }}"
    enctype="multipart/form-data"
    class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6"
    data-expense-receipt-scan
    data-expense-scan-url="{{ route('expenses.scan-receipt') }}"
    data-expense-scan-enabled="{{ $receiptScanAvailable ? '1' : '0' }}"
    data-expense-scan-loading-message="{{ __('expenses.scan_loading') }}"
    data-expense-scan-success-message="{{ __('expenses.scan_success') }}"
    data-expense-scan-failed-message="{{ __('expenses.scan_failed') }}"
    data-expense-scan-disabled-message="{{ __('expenses.scan_not_configured') }}"
    data-expense-scan-pdf-message="{{ __('expenses.scan_pdf_unsupported') }}">
    @csrf

    <div class="mb-6 rounded-xl border border-dashed border-ziifra-line bg-ziifra-cream/40 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-ziifra-ink">{{ __('expenses.receipt_upload_title') }}</p>
                <p class="mt-1 text-sm text-ziifra-muted">{{ __('expenses.receipt_upload_hint') }}</p>
            </div>
            <div class="shrink-0">
                <label for="receipt" class="ziifra-btn-app-outline cursor-pointer">{{ __('expenses.receipt_choose') }}</label>
                <input type="file" id="receipt" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only">
            </div>
        </div>

        <div data-expense-scan-preview hidden class="mt-4 overflow-hidden rounded-lg border border-ziifra-line/80 bg-ziifra-paper">
            <img data-expense-scan-preview-image alt="" class="max-h-48 w-full object-contain bg-ziifra-cream/30">
        </div>

        <div data-expense-scan-status hidden class="mt-4"></div>

        @error('receipt')<p class="mt-3 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @if ($canCreateForOthers)
            <div class="sm:col-span-2">
                <label for="employee_id" class="ziifra-label-field">{{ __('expenses.employee') }}</label>
                <select id="employee_id" name="employee_id" required class="ziifra-input">
                    <option value="" disabled @selected(! old('employee_id'))>{{ __('expenses.select_employee') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
                @error('employee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        @endif
        <div>
            <label for="category" class="ziifra-label-field">{{ __('expenses.category') }}</label>
            <select id="category" name="category" required class="ziifra-input">
                @foreach (\App\Enums\ExpenseCategory::cases() as $category)
                    <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            @error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="expense_date" class="ziifra-label-field">{{ __('expenses.expense_date') }}</label>
            <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required class="ziifra-input">
            @error('expense_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="title" class="ziifra-label-field">{{ __('expenses.description') }}</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="255" class="ziifra-input" placeholder="{{ __('expenses.description_placeholder') }}">
            @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="amount" class="ziifra-label-field">{{ __('expenses.amount') }}</label>
            <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="{{ old('amount') }}" required class="ziifra-input">
            @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="notes" class="ziifra-label-field">{{ __('expenses.notes') }}</label>
            <textarea id="notes" name="notes" rows="2" maxlength="2000" class="ziifra-input">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="mt-6 flex gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('expenses.submit') }}</button>
        <a href="{{ route('expenses.index') }}" class="ziifra-btn-app-outline">{{ __('common.cancel') }}</a>
    </div>
</form>
@endsection
