@extends('layouts.app')

@section('title', __('documents.title'))
@section('header', __('documents.title'))

@section('content')
@php
    use App\Enums\EmployeeDocumentType;

    $selectedTypeEnum = $selectedType
        ? EmployeeDocumentType::tryFrom($selectedType)
        : null;
    $filterQuery = array_filter([
        'folder' => $selectedFolder?->id,
        'type' => $selectedType,
        'search' => request('search'),
        'employee_id' => request('employee_id'),
        'expiry' => request('expiry'),
    ]);
    $folderFileCount = $selectedFolder
        ? ($selectedFolder->documents_count ?? $documents->total())
        : ($selectedTypeEnum ? ($typeCounts[$selectedType] ?? 0) : null);
@endphp

<div class="ziifra-documents space-y-6">
    @if (! $showFolderContents)
        @include('app.documents._library-header')
        @include('app.documents._folders')
    @else
        @include('app.documents._breadcrumb')
        @include('app.documents._folder-header', ['fileCount' => $folderFileCount])

        <div @class(['ziifra-documents-layout', 'ziifra-documents-layout--solo' => ! $canManage])>
            <div class="ziifra-documents-main space-y-4">
                @include('app.documents._filters')
                @include('app.documents._file-list')

                @if ($selectedType === EmployeeDocumentType::Contract->value && count($contractTemplates) > 0 && ! $canManage)
                    @include('app.documents._contract-templates')
                @endif
            </div>

            @if ($canManage)
                <aside class="ziifra-documents-aside space-y-4">
                    @include('app.documents._upload-panel')

                    @if ($selectedType === EmployeeDocumentType::Contract->value && count($contractTemplates) > 0)
                        @include('app.documents._contract-templates')
                    @endif
                </aside>
            @endif
        </div>
    @endif
</div>
@endsection
