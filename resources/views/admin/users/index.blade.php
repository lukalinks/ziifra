@extends('admin.layout')

@section('title', __('admin.nav.users'))

@section('content')
<div>
    <h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.users.heading') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('admin.users.subtitle') }}</p>
</div>

<form method="GET" class="mt-6 flex flex-wrap items-center gap-3">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.users.search_placeholder') }}"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="super_admin" value="1" @checked(request()->boolean('super_admin')) class="rounded border-slate-300">
        {{ __('admin.users.filter_super_admin') }}
    </label>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filter</button>
</form>

<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-ziifra-paper">
    <div class="ziifra-table-scroll">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-3 font-medium">User</th>
                <th class="px-4 py-3 font-medium">Organizations</th>
                <th class="px-4 py-3 font-medium">Platform</th>
                <th class="px-4 py-3 font-medium">Joined</th>
                <th class="px-4 py-3 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->organizations_count }}</td>
                    <td class="px-4 py-3">
                        @if ($user->isSuperAdmin())
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">Super admin</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-indigo-600 hover:text-indigo-700">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
