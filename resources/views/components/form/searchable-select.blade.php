@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'emptyText' => null,
    'required' => false,
    'hint' => null,
])

@php
    $selectedValue = (string) ($selected ?? '');
    $selectedLabel = collect($options)->firstWhere('value', $selectedValue)['label'] ?? '';
    $listId = $name.'-searchable-list';
@endphp

<div {{ $attributes->class(['ziifra-searchable-select']) }} data-searchable-select>
    @if ($label)
        <label for="{{ $name }}_search" class="block text-sm font-medium text-ziifra-ink">
            {{ $label }}
            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative mt-1" data-searchable-trigger>
        <input type="hidden" name="{{ $name }}" value="{{ $selectedValue }}" @if($required) required @endif data-searchable-value>
        <input type="text"
            id="{{ $name }}_search"
            value="{{ $selectedLabel }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-controls="{{ $listId }}"
            aria-autocomplete="list"
            data-searchable-input
            @class([
                'ziifra-searchable-select-input',
                'ring-2 ring-ziifra-accent/30 border-ziifra-accent' => $errors->has($name),
            ])>
        <span class="ziifra-searchable-select-icon" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
        </span>
    </div>

    <ul id="{{ $listId }}"
        role="listbox"
        hidden
        data-searchable-list
        data-empty-text="{{ $emptyText }}"
        class="ziifra-searchable-select-list">
        @foreach ($options as $option)
            <li role="option"
                data-value="{{ $option['value'] }}"
                data-label="{{ $option['label'] }}"
                @class(['ziifra-searchable-select-option', 'is-selected' => (string) $option['value'] === $selectedValue])>
                {{ $option['label'] }}
            </li>
        @endforeach
    </ul>

    @if ($hint)
        <p class="mt-1 text-xs text-ziifra-muted">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
