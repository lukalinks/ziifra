@props([
    'feed',
])

@php
    /** @var \App\Support\NotificationFeed $feed */
    $unread = $feed->unreadCount;
    $isAdminHeader = request()->routeIs('admin.*');
@endphp

<div @class(['ziifra-notifications', 'ziifra-notifications-admin' => $isAdminHeader])
    data-notifications
    data-csrf-token="{{ csrf_token() }}"
    data-read-all-url="{{ route('notifications.read-all') }}"
    @if ($isAdminHeader) data-notifications-admin @endif>
    <button type="button"
        class="ziifra-notifications-trigger"
        data-notifications-toggle
        aria-expanded="false"
        aria-haspopup="dialog"
        aria-controls="ziifra-notifications-panel"
        aria-label="{{ __('notifications.open', ['count' => $unread]) }}">
        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if ($unread > 0)
            <span class="ziifra-notifications-badge" data-notifications-badge aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div class="ziifra-notifications-backdrop" data-notifications-backdrop hidden aria-hidden="true"></div>

    <div id="ziifra-notifications-panel"
        class="ziifra-notifications-panel"
        data-notifications-panel
        hidden
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('notifications.panel_title') }}">
        <div class="ziifra-notifications-panel-head">
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <span class="ziifra-notifications-head-icon" aria-hidden="true">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('notifications.panel_title') }}</h2>
                    <p class="text-[0.68rem] text-ziifra-muted" data-notifications-subtitle>{{ __('notifications.open', ['count' => $unread]) }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($feed->canMarkAllRead && $unread > 0)
                    <button type="button"
                        class="text-xs font-medium text-ziifra-accent-deep hover:underline"
                        data-notifications-mark-all>
                        {{ __('notifications.mark_all_read') }}
                    </button>
                @endif
                <button type="button"
                    class="ziifra-notifications-close"
                    data-notifications-close
                    aria-label="{{ __('common.close') }}">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        @if ($feed->items->isEmpty())
            <p class="ziifra-notifications-empty">{{ __('notifications.empty') }}</p>
        @else
            <ul class="ziifra-notifications-list" data-notifications-list>
                @foreach ($feed->items as $item)
                    <li @class(['ziifra-notifications-item', 'ziifra-notifications-item-unread' => ! $item->read])
                        data-notification-item
                        @if (! $item->read) data-notification-unread @endif
                        data-notification-id="{{ $item->id }}"
                        @if (! $item->read) data-notification-read-url="{{ route('notifications.read', $item->id) }}" @endif>
                        @if (! $item->read)
                            <span class="ziifra-notifications-unread-dot" aria-hidden="true"></span>
                        @endif
                        @if ($item->url)
                            <a href="{{ $item->url }}"
                                class="ziifra-notifications-link"
                                data-notification-link
                                @if (! $item->read) data-page-nav @endif>
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
                            <button type="button"
                                class="ziifra-notifications-dismiss-btn"
                                data-notification-dismiss
                                data-notification-read-url="{{ route('notifications.read', $item->id) }}"
                                aria-label="{{ __('notifications.dismiss') }}">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
