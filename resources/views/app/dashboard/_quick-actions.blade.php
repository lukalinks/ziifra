<div class="grid grid-cols-1 gap-2 sm:grid-cols-2 {{ ($columns ?? 4) === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4' }}">
    @foreach ($quickActions as $action)
        <x-dashboard.action
            :href="route($action['route'], $action['params'] ?? [])"
            :icon="$action['icon']"
            :label="$action['label']"
        />
    @endforeach
</div>
