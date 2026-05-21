@php
    $alert = session('alert');
    $alertIsArray = is_array($alert);
@endphp

@if ($errors->any())
    <x-ui.alert-banner variant="danger" :title="__('common.flash.validation_heading')">
        <ul class="ziifra-flash-errors list-none pl-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert-banner>
@endif

@if (session('error'))
    <x-ui.alert-banner variant="danger" :title="__('common.flash.error_heading')">
        <p>{{ session('error') }}</p>
    </x-ui.alert-banner>
@endif

@if (session('warning'))
    <x-ui.alert-banner variant="warning" :title="__('common.flash.warning_heading')">
        <p>{{ session('warning') }}</p>
    </x-ui.alert-banner>
@endif

@if ($alertIsArray)
    @php
        $variant = $alert['variant'] ?? 'info';
        $alertTitle = $alert['title'] ?? null;
        $body = $alert['body'] ?? null;
        $message = $alert['message'] ?? null;
    @endphp
    @if (filled($body) || filled($message) || filled($alertTitle))
        <x-ui.alert-banner :variant="$variant" :title="$alertTitle">
            @if (filled($body))
                <p>{{ $body }}</p>
            @elseif (filled($message))
                <p>{{ $message }}</p>
            @endif
        </x-ui.alert-banner>
    @endif
@endif

@if (! $alertIsArray && session('success'))
    <x-ui.alert-banner variant="success">
        <p>{{ session('success') }}</p>
    </x-ui.alert-banner>
@elseif (! $alertIsArray && session('status'))
    <x-ui.alert-banner variant="success">
        <p>{{ session('status') }}</p>
    </x-ui.alert-banner>
@endif
