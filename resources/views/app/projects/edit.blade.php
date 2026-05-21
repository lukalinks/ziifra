@extends('layouts.app')

@section('title', __('projects.edit'))
@section('header', __('projects.edit'))

@section('content')
<form method="POST" action="{{ route('projects.update', $project) }}" class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    @method('PUT')
    @include('app.projects._form', ['project' => $project, 'statuses' => $statuses, 'employees' => $employees])
    <div class="mt-6 flex gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('projects.save') }}</button>
        <a href="{{ route('projects.show', $project) }}" class="ziifra-btn-app-outline">Cancel</a>
    </div>
</form>
@endsection
