@extends('layouts.app')

@section('title', __('settings_payroll.title'))
@section('header', __('settings_payroll.title'))

@section('content')
<p class="mb-6"><a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep">← {{ __('settings.back') }}</a></p>

@php $ps = $payrollSettings; $pt = $payslipTpl; @endphp

<form method="POST" action="{{ route('settings.payroll.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-8">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 space-y-4">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings_payroll.title') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings_payroll.trust_employee') }}</label>
                <input type="number" step="0.01" name="payroll_settings[trust_employee_percent]" value="{{ old('payroll_settings.trust_employee_percent', $ps['trust_employee_percent']) }}" class="mt-1 ziifra-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings_payroll.trust_employer') }}</label>
                <input type="number" step="0.01" name="payroll_settings[trust_employer_percent]" value="{{ old('payroll_settings.trust_employer_percent', $ps['trust_employer_percent']) }}" class="mt-1 ziifra-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings_payroll.vat_percent') }}</label>
                <input type="number" step="0.01" name="payroll_settings[vat_percent]" value="{{ old('payroll_settings.vat_percent', $ps['vat_percent']) }}" class="mt-1 ziifra-input w-full">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="payroll_settings[show_logo]" value="1" @checked($ps['show_logo'] ?? true)> {{ __('settings.company.payslip_show_logo') }}</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="payroll_settings[show_vat]" value="1" @checked($ps['show_vat'] ?? true)> Show TVSH on document</label>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.bank_name') }}</label>
            <input name="bank_name" value="{{ old('bank_name', $organization->bank_name) }}" class="mt-1 ziifra-input w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.bank_iban') }}</label>
            <input name="bank_iban" value="{{ old('bank_iban', $organization->bank_iban) }}" class="mt-1 ziifra-input w-full font-mono text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('settings.company.company_logo') }}</label>
            <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="mt-1 text-sm">
        </div>
    </section>

    <button type="submit" class="ziifra-btn-app">{{ __('settings.company.save') }}</button>
</form>
@endsection
