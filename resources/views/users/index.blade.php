@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('content')
<x-page-head
    eyebrow="Akun & Hak Akses"
    title="Manajemen Pengguna"
    description="Kelola akun yang dapat masuk ke aplikasi, beserta peran dan sekolahnya."
>
    <x-slot:actions>
        <a href="{{ route('permissions.index') }}" class="btn btn-ghost btn-sm">Matriks Hak Akses</a>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Pengguna
        </a>
    </x-slot:actions>
</x-page-head>

@if($errors->any())
    <div class="panel border-danger p-4 mb-6" role="alert">
        <p class="text-sm text-danger">{{ $errors->first() }}</p>
    </div>
@endif

<form method="GET" action="{{ route('users.index') }}" class="panel p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
    <div class="lg:col-span-2">
        <label for="q" class="label">Cari</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Nama atau email" class="input">
    </div>

    <div>
        <label for="role" class="label">Peran</label>
        <select id="role" name="role" class="input">
            <option value="">Semua peran</option>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if($canChooseTenant)
        <div>
            <label for="tenant" class="label">Sekolah</label>
            <select id="tenant" name="tenant" class="input">
                <option value="">Semua sekolah</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(request('tenant') === $tenant->id)>{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="flex gap-2 lg:col-span-4">
        <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
        @if(request()->hasAny(['q', 'role', 'tenant']))
            <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
    </div>
</form>

@if($users->isEmpty())
    <x-panel class="p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </span>
        <p class="empty-state__title">Tidak ada pengguna yang cocok</p>
        <p class="empty-state__hint">Ubah kata kunci atau filter peran yang dipakai.</p>
    </x-panel>
@else
    <x-panel class="overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Nama</th>
                        <th>Peran</th>
                        <th>Sekolah</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($users as $i => $user)
                        @php $manageable = auth()->user()->canManage($user); @endphp
                        <tr>
                            <td class="text-text-muted">{{ $users->firstItem() + $i }}</td>
                            <td>
                                <span class="font-medium">{{ $user->name }}</span>
                                @if($user->is(auth()->user()))
                                    <span class="text-xs text-text-muted">(Anda)</span>
                                @endif
                                <span class="block text-xs text-text-muted">{{ $user->email }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $user->isSuperAdmin() ? 'badge-warning' : ($user->isAdminSekolah() ? 'badge-success' : '') }}">{{ roleLabel($user->role) }}</span>
                            </td>
                            <td class="text-text-muted">{{ $user->tenant?->name ?? 'Platform' }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if($manageable)
                                    <div class="inline-flex items-center gap-4">
                                        <a href="{{ route('users.edit', $user) }}" class="link">Edit</a>
                                        @unless($user->is(auth()->user()))
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" data-confirm="Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link link-danger">Hapus</button>
                                            </form>
                                        @endunless
                                    </div>
                                @else
                                    <span class="text-xs text-text-muted">Di luar kewenangan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">
            {{ $users->links() }}
        </div>
    </x-panel>
@endif
@endsection
