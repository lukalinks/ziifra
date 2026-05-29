@php
    $columnCount = (int) ($columns ?? 4);
    $gridClass = match ($columnCount) {
        1 => 'grid-cols-1',
        3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };
@endphp
<div class="grid gap-2 {{ $gridClass }}">
    @foreach ($quickActions as $action)
        <x-dashboard.action
            :href="route($action['route'], $action['params'] ?? [])"
            :icon="$action['icon']"
            :label="$action['label']"
        />
    @endforeach
</div>
