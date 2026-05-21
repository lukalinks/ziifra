@props([
    'feed',
])

@php
    /** @var \App\Support\NotificationFeed $feed */
    $unread = $feed->unreadCount;
    $isAdminHeader = request()->routeIs('admin.*');
@endphp

<div @class(['ziifra-notifications', 'ziifra-notifications-admin' => $isAdminHeader]) data-notifications>
    <button type="button"
        class="ziifra-notifications-trigger"
        data-notifications-toggle
        aria-expanded="false"
        aria-haspopup="true"
        aria-controls="ziifra-notifications-panel"
        aria-label="{{ __('notifications.open', ['count' => $unread]) }}">
        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if ($unread > 0)
            <span class="ziifra-notifications-badge" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div id="ziifra-notifications-panel"
        class="ziifra-notifications-panel"
        data-notifications-panel
        hidden
        role="region"
        aria-label="{{ __('notifications.panel_title') }}">
        <div class="ziifra-notifications-panel-head">
            <div class="flex items-center gap-2">
                <span class="ziifra-notifications-head-icon" aria-hidden="true">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('notifications.panel_title') }}</h2>
                    <p class="text-[0.68rem] text-ziifra-muted">{{ __('notifications.open', ['count' => $unread]) }}</p>
                </div>
            </div>
            @if ($feed->canMarkAllRead && $unread > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @if (request()->routeIs('admin.*'))
                        <input type="hidden" name="admin" value="1">
                    @endif
                    <button type="submit" class="text-xs font-medium text-ziifra-accent-deep hover:underline">
                        {{ __('notifications.mark_all_read') }}
                    </button>
                </form>
            @endif
        </div>

        @if ($feed->items->isEmpty())
            <p class="ziifra-notifications-empty">{{ __('notifications.empty') }}</p>
        @else
            <ul class="ziifra-notifications-list">
                @foreach ($feed->items as $item)
                    <li @class(['ziifra-notifications-item', 'ziifra-notifications-item-unread' => ! $item->read])>
                        @if (! $item->read)
                            <span class="ziifra-notifications-unread-dot" aria-hidden="true"></span>
                        @endif
                        @if ($item->url)
                            <a href="{{ $item->url }}" class="ziifra-notifications-link" @if(! $item->read && ! $item->ephemeral) data-page-nav @endif>
                                <span class="ziifra-notifications-link-title">{{ $item->title }}</span>
                                <span class="ziifra-notifications-link-body">{{ $item->body }}</span>
                                <span class="ziifra-notifications-link-time">{{ $item->createdAt->diffForHumans() }}</span>
                            </a>
                        @else
                            <div class="ziifra-notifications-link">
                                <span class="ziifra-notifications-link-title">{{ $item->title }}</span>
                                <span class="ziifra-notifications-link-body">{{ $item->body }}</span>
                                <span class="ziifra-notifications-link-time">{{ $item->createdAt->diffForHumans() }}</span>
                            </div>
                        @endif
                        @if (! $item->read)
                            <form method="POST" action="{{ route('notifications.read', $item->id) }}" class="ziifra-notifications-dismiss">
                                @csrf
                                <button type="submit" class="text-xs text-ziifra-muted hover:text-ziifra-ink" aria-label="{{ __('notifications.dismiss') }}">×</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
