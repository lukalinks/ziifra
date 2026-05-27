@extends('layouts.app')

@section('title', __('chat.title'))
@section('header', __('chat.title'))

@section('content')
<p class="mb-4 hidden text-sm text-ziifra-muted md:block">{{ __('chat.subtitle') }}</p>

<div class="ziifra-chat-mobile lg:mt-6 lg:flex lg:min-h-[20rem] lg:flex-row lg:gap-6">
    <div class="ziifra-chat-mobile-messages order-2 flex min-h-[16rem] flex-1 flex-col lg:order-1 lg:min-h-[20rem] lg:rounded-xl lg:border lg:border-ziifra-line/80 lg:bg-ziifra-paper">
        <div class="flex-1 space-y-4 overflow-y-auto p-3 lg:p-4" id="chat-messages">
            @forelse ($messages as $message)
                <article @class([
                    'rounded-xl px-3 py-2',
                    'bg-ziifra-accent/10 ml-0 sm:ml-8' => $message->user_id === auth()->id(),
                    'bg-ziifra-cream/60 mr-0 sm:mr-8' => $message->user_id !== auth()->id(),
                ])>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-ziifra-ink">
                            {{ $message->user_id === auth()->id() ? __('chat.you') : $message->user->name }}
                            <span class="font-normal text-ziifra-muted">· {{ $message->created_at->diffForHumans() }}</span>
                        </p>
                        @can('delete', $message)
                            <form method="POST" action="{{ route('chat.destroy', $message) }}" data-confirm="{{ __('chat.confirm_delete') }}" data-confirm-variant="danger" data-confirm-accept="{{ __('common.delete') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('chat.delete') }}</button>
                            </form>
                        @endcan
                    </div>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-ziifra-ink">{{ $message->body }}</p>
                </article>
            @empty
                <p class="py-8 text-center text-sm text-ziifra-muted">{{ __('chat.empty') }}</p>
            @endforelse
        </div>
        @if ($messages->hasPages())
            <div class="border-t px-4 py-3">{{ $messages->links() }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('chat.store') }}" class="ziifra-chat-mobile-compose order-1 shrink-0 lg:order-2 lg:w-80 lg:rounded-xl lg:border lg:border-ziifra-line/80 lg:bg-ziifra-paper lg:p-4">
        @csrf
        <label for="body" class="mb-2 block text-sm font-medium text-ziifra-ink">{{ __('chat.send') }}</label>
        <textarea name="body" id="body" rows="4" required maxlength="2000" placeholder="{{ __('chat.placeholder') }}"
            class="ziifra-input !mt-0 min-h-[5rem] lg:rows-5">{{ old('body') }}</textarea>
        @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="ziifra-btn-primary mt-3 w-full">{{ __('chat.send') }}</button>
    </form>
</div>
@endsection
