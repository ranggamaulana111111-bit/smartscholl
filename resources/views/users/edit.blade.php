@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('content')
<x-page-head
    eyebrow="Akun & Hak Akses"
    :title="'Edit: ' . $user->name"
    description="Perbarui data akun, peran, dan kata sandinya."
>
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('users.update', $user) }}" class="max-w-3xl panel p-6 sm:p-8 space-y-6" data-busy novalidate>
    @csrf
    @method('PUT')

    @include('users._form')

    <div class="flex items-center gap-3 pt-2 border-t border-border">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('users.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>

@if($overridable)
    <form method="POST" action="{{ route('users.permissions.update', $user) }}"
        class="max-w-5xl panel p-6 sm:p-8 mt-8 space-y-6" data-busy novalidate>
        @csrf
        @method('PUT')

        <div>
            <h2 class="font-display text-lg font-bold text-text">Pengecualian Akses</h2>
            <p class="mt-1 text-sm text-text-muted">
                Izin bawaan {{ $user->name }} mengikuti peran {{ roleLabel($user->role) }}. Ubah hanya bila akun ini
                perlu berbeda dari perannya.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Izin</th>
                        <th class="w-56">Sumber</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($permissionRows as $row)
                        @php $current = old("overrides.{$loop->index}.effect", $overrides[$row['name']] ?? 'inherit'); @endphp
                        <tr>
                            <td>
                                <span class="font-medium">{{ $row['label'] }}</span>
                                <span class="block text-xs text-text-muted">{{ $row['group_label'] }} &middot; {{ $row['name'] }}</span>
                            </td>
                            <td>
                                <input type="hidden" name="overrides[{{ $loop->index }}][permission]" value="{{ $row['name'] }}">
                                <select name="overrides[{{ $loop->index }}][effect]"
                                    class="input @error('overrides.'.$loop->index.'.effect') border-danger @enderror"
                                    aria-label="Sumber izin {{ $row['label'] }}">
                                    <option value="inherit" @selected($current === 'inherit')>Ikuti peran</option>
                                    <option value="grant" @selected($current === 'grant')>Izinkan</option>
                                    <option value="deny" @selected($current === 'deny')>Larang</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @error('overrides')
            <p class="error-text">{{ $message }}</p>
        @enderror

        <div class="flex items-center gap-3 pt-2 border-t border-border">
            <button type="submit" class="btn btn-primary">Simpan Pengecualian</button>
            <a href="{{ route('users.index') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
@endif
@endsection
