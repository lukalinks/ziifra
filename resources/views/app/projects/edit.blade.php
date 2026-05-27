@extends('layouts.app')

@section('title', __('projects.edit'))
@section('header', __('projects.edit'))

@section('content')
<div class="ziifra-dashboard-page mx-auto max-w-3xl">
    <a href="{{ route('projects.show', $project) }}" class="ziifra-employee-profile-back" data-page-nav>
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $project->name }}
    </a>

    <div class="mt-4 rounded-2xl border border-ziifra-line/80 bg-ziifra-paper p-6 sm:p-8">
        <h1 class="text-xl font-semibold text-ziifra-ink">{{ __('projects.edit') }}</h1>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('projects.edit_subtitle') }}</p>

        <form method="POST" action="{{ route('projects.update', $project) }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')
            @include('app.projects._form', ['project' => $project, 'statuses' => $statuses, 'employees' => $employees])
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="ziifra-btn-primary w-full justify-center sm:w-auto">{{ __('projects.save') }}</button>
                <a href="{{ route('projects.show', $project) }}" class="ziifra-btn-app-outline w-full justify-center sm:w-auto" data-page-nav>{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
