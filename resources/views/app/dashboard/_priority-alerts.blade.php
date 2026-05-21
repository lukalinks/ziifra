<div class="ziifra-dashboard-alerts">
    @foreach ($priorityAlerts as $alert)
        <a href="{{ $alert['href'] }}"
            class="ziifra-dashboard-alert ziifra-dashboard-alert-{{ $alert['variant'] }}">
            @if (($alert['count'] ?? 0) > 0)
                <span class="ziifra-dashboard-alert-count">{{ $alert['count'] }}</span>
            @endif
            <span class="min-w-0 flex-1 leading-snug">{{ $alert['label'] }}</span>
        </a>
    @endforeach
</div>
