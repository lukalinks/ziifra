@extends('layouts.app')

@section('title', __('projects.new'))
@section('header', __('projects.new'))

@section('content')
<form method="POST" action="{{ route('projects.store') }}" class="max-w-3xl rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
    @csrf
    @include('app.projects._form', ['project' => null, 'statuses' => $statuses, 'employees' => $employees])
    <div class="mt-6 flex gap-3">
        <button type="submit" class="ziifra-btn-primary">{{ __('projects.save') }}</button>
        <a href="{{ route('projects.index') }}" class="ziifra-btn-app-outline">Cancel</a>
    </div>
</form>
@endsection
