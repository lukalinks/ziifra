@extends('layouts.app')

@section('title', __('chat.title'))
@section('header', __('chat.title'))

@section('content')
<p class="mb-4 hidden text-sm text-ziifra-muted md:block">{{ __('chat.subtitle') }}</p>

<div class="ziifra-chat-layout">
    @if ($privateEnabled)
        <aside class="ziifra-chat-sidebar" aria-label="{{ __('chat.channels') }}">
            <p class="ziifra-chat-sidebar-label">{{ __('chat.channels') }}</p>
            <nav class="ziifra-chat-channel-list">
                <a href="{{ route('chat.index') }}" data-page-nav
                    @class(['ziifra-chat-channel', 'ziifra-chat-channel--active' => $isTeamChannel])>
                    <span class="ziifra-chat-channel-icon" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                    </span>
                    <span class="min-w-0 truncate">{{ __('chat.team_channel') }}</span>
                </a>
            </nav>

            @if ($partners->isNotEmpty())
                <p class="ziifra-chat-sidebar-label mt-4">{{ __('chat.private_messages') }}</p>
                <nav class="ziifra-chat-channel-list">
                    @foreach ($partners as $partner)
                        <a href="{{ route('chat.index', ['with' => $partner->id]) }}" data-page-nav
                            @class(['ziifra-chat-channel', 'ziifra-chat-channel--active' => $activePartner?->id === $partner->id])>
                            <span class="ziifra-chat-channel-avatar" aria-hidden="true">{{ strtoupper(substr($partner->name, 0, 1)) }}</span>
                            <span class="min-w-0 truncate">{{ $partner->name }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif
        </aside>
    @endif

    <div class="ziifra-chat-main">
        <header class="ziifra-chat-main-head">
            <h2 class="text-sm font-semibold text-ziifra-ink">
                @if ($isTeamChannel)
                    {{ __('chat.team_channel') }}
                @else
                    {{ $activePartner->name }}
                @endif
            </h2>
            <p class="text-xs text-ziifra-muted">
                {{ $isTeamChannel ? __('chat.team_channel_hint') : __('chat.private_channel_hint') }}
            </p>
        </header>

        <div class="ziifra-chat-messages" id="chat-messages">
            @forelse ($messages as $chatMessage)
                <article @class([
                    'ziifra-chat-bubble',
                    'ziifra-chat-bubble--mine' => $chatMessage->user_id === auth()->id(),
                    'ziifra-chat-bubble--theirs' => $chatMessage->user_id !== auth()->id(),
                ])>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-ziifra-ink">
                            {{ $chatMessage->user_id === auth()->id() ? __('chat.you') : $chatMessage->user->name }}
                            <span class="font-normal text-ziifra-muted">· {{ $chatMessage->created_at->diffForHumans() }}</span>
                        </p>
                        @can('delete', $chatMessage)
                            <form method="POST" action="{{ route('chat.destroy', $chatMessage) }}" data-confirm="{{ __('chat.confirm_delete') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('common.delete') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('chat.delete') }}</button>
                            </form>
                        @endcan
                    </div>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-ziifra-ink">{{ $chatMessage->body }}</p>
                </article>
            @empty
                <p class="py-8 text-center text-sm text-ziifra-muted">
                    {{ $isTeamChannel ? __('chat.empty') : __('chat.empty_private') }}
                </p>
            @endforelse
        </div>

        @if ($messages->hasPages())
            <div class="border-t border-ziifra-line/70 px-3 py-2">{{ $messages->links() }}</div>
        @endif

        @if ($canWrite ?? true)
            <form method="POST" action="{{ route('chat.store') }}" class="ziifra-chat-compose">
                @csrf
                @if ($activePartner)
                    <input type="hidden" name="recipient_user_id" value="{{ $activePartner->id }}">
                @endif
                <label for="body" class="sr-only">{{ __('chat.send') }}</label>
                <textarea name="body" id="body" rows="3" required maxlength="2000" placeholder="{{ $isTeamChannel ? __('chat.placeholder') : __('chat.placeholder_private', ['name' => $activePartner->name]) }}"
                    class="ziifra-input !mt-0 min-h-[4rem]">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-red-600">{{ $errors->first('body') }}</p>@enderror
                @error('recipient_user_id')<p class="mt-1 text-xs text-red-600">{{ $errors->first('recipient_user_id') }}</p>@enderror
                <button type="submit" class="ziifra-btn-primary mt-2 w-full sm:w-auto">{{ __('chat.send') }}</button>
            </form>
        @else
            <p class="ziifra-chat-compose ziifra-chat-compose--readonly text-sm text-ziifra-muted">{{ __('chat.employees_cannot_write') }}</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('chat-messages')?.scrollTo(0, document.getElementById('chat-messages').scrollHeight);
</script>
@endpush
@endsection
