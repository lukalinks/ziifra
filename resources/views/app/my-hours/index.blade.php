@extends('layouts.app')

@section('title', __('my_hours.title'))
@section('header', __('my_hours.title'))

@section('content')
<div class="ziifra-dashboard-page" data-payroll-time
    data-upsert-url="{{ route('my-hours.upsert', $organization) }}"
    data-project-id="{{ $selectedProject?->id }}"
    data-csrf="{{ csrf_token() }}">

    <p class="mb-4 text-sm text-ziifra-muted">{{ __('my_hours.subtitle') }}</p>

    @if ($projects->isEmpty())
        <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6 text-sm text-ziifra-muted">
            {{ __('my_hours.no_projects') }}
        </div>
    @else
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label for="mh-project" class="ziifra-label-field">{{ __('my_hours.project') }}</label>
                <select id="mh-project" name="project_id" class="ziifra-input mt-1" onchange="this.form.submit()">
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected($selectedProject?->id === $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="mh-month" class="ziifra-label-field">{{ __('payroll_time.month') }}</label>
                <select id="mh-month" name="month" class="ziifra-input mt-1" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        @php $opt = \Carbon\Carbon::create($year, $m, 1); @endphp
                        <option value="{{ $m }}" @selected($month === $m)>{{ $opt->format('F Y') }}</option>
                    @endfor
                </select>
                <input type="hidden" name="year" value="{{ $year }}">
            </div>
        </form>

        @include('app.payroll-time._grid', [
            'organization' => $organization,
            'grid' => $grid,
            'year' => $year,
            'month' => $month,
            'projects' => $projects,
            'linkedEmployee' => $employee,
            'search' => '',
            'canManage' => false,
            'canApprove' => false,
        ])

        <p class="mt-3 text-xs text-ziifra-muted">{{ __('my_hours.pending_note') }}</p>
    @endif
</div>

@push('scripts')
    @vite('resources/js/payroll-time-grid.js')
@endpush
@endsection
