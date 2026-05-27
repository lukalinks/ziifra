<nav class="mb-4 flex flex-wrap items-center justify-between gap-4 sm:mb-8">
    <div class="ziifra-leave-tabs">
        <a href="{{ route('leave.index') }}"
            data-page-nav
            @class([
                'ziifra-leave-tab',
                request()->routeIs('leave.index') || request()->routeIs('leave.show') || request()->routeIs('leave.create')
                    ? 'ziifra-leave-tab-active'
                    : '',
            ])>
            {{ __('leave.nav.requests') }}
        </a>
        <a href="{{ route('leave.calendar', request()->only(['year', 'month', 'pending'])) }}"
            data-page-nav
            @class([
                'ziifra-leave-tab',
                request()->routeIs('leave.calendar') ? 'ziifra-leave-tab-active' : '',
            ])>
            {{ __('leave.nav.calendar') }}
        </a>
    </div>
</nav>
