@extends('layouts.app')

@section('title', __('project_documents.title'))
@section('header', __('project_documents.title'))

@section('content')
<div class="ziifra-documents space-y-6">
    @if (! $showProjectContents)
        @include('app.project-documents._library-header')
        @include('app.project-documents._projects')
        @if ($recentDocuments->isNotEmpty())
            @include('app.project-documents._recent')
        @endif
    @else
        @include('app.project-documents._breadcrumb')
        @include('app.project-documents._project-header')

        <div @class(['ziifra-documents-layout', 'ziifra-documents-layout--solo' => ! $canManage])>
            <div class="ziifra-documents-main space-y-4">
                @include('app.project-documents._filters')
                @include('app.project-documents._file-list')
            </div>

            @if ($canManage)
                <aside class="ziifra-documents-aside space-y-4">
                    @include('app.project-documents._upload-panel')
                    @include('app.project-documents._export-panel')
                    @include('app.project-documents._nav-category')
                </aside>
            @endif
        </div>
    @endif
</div>
@endsection
