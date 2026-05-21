@extends('layouts.app')

@section('title', 'Import employees')
@section('header', 'Import employees')

@section('content')
<div class="mx-auto max-w-2xl">
    <p class="text-sm text-ziifra-muted">{{ __('import.subtitle') }}</p>

    @if ($result = session('import_result'))
        <div class="mt-6 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-5">
            <p class="text-sm font-medium text-ziifra-ink">
                {{ __('import.result_imported', ['count' => $result['imported']]) }}
            </p>
            @if ($result['skipped'] > 0)
                <p class="mt-1 text-sm text-amber-800">
                    {{ __('import.result_skipped', ['count' => $result['skipped']]) }}
                </p>
            @endif
            @if (! empty($result['errors']))
                <h3 class="mt-4 text-sm font-semibold text-ziifra-ink">{{ __('import.errors_title') }}</h3>
                <ul class="mt-2 max-h-48 space-y-1 overflow-y-auto text-sm text-red-700">
                    @foreach ($result['errors'] as $error)
                        <li>Row {{ $error['row'] }}: {{ $error['message'] }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($result['imported'] > 0)
                <a href="{{ route('employees.index') }}" class="mt-4 inline-block text-sm font-medium text-ziifra-accent-deep hover:underline">
                    View employees →
                </a>
            @endif
        </div>
    @endif

    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('employees.import.template') }}" class="ziifra-btn-app-outline">
            {{ __('import.download_template') }}
        </a>
        <a href="{{ route('employees.create') }}" class="text-sm font-medium text-ziifra-muted hover:text-ziifra-ink">
            Add one employee instead
        </a>
    </div>

    <form method="POST" action="{{ route('employees.import.store') }}" enctype="multipart/form-data"
        class="mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        @csrf
        <label for="file" class="ziifra-label-field">{{ __('import.file_label') }}</label>
        <input type="file" id="file" name="file" accept=".csv,text/csv" required
            class="mt-2 w-full text-sm text-ziifra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-ziifra-cream file:px-3 file:py-2 file:text-sm file:font-medium file:text-ziifra-ink">
        <p class="mt-2 text-xs text-ziifra-muted">{{ __('import.file_hint') }}</p>
        @error('file')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="ziifra-btn-app mt-6">
            {{ __('import.upload') }}
        </button>
    </form>

    <div class="mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-cream/40 p-6">
        <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('import.columns_title') }}</h2>
        <dl class="mt-4 space-y-2 text-sm">
            @foreach (__('import.columns') as $key => $label)
                <div class="flex gap-3">
                    <dt class="w-36 shrink-0 font-mono text-xs text-ziifra-accent-deep">{{ $key }}</dt>
                    <dd class="text-ziifra-muted">{{ $label }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <a href="{{ route('employees.index') }}" class="mt-8 inline-block text-sm text-ziifra-accent-deep hover:underline">← Back to employees</a>
</div>
@endsection
