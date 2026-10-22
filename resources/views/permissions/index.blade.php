@extends('layouts.app')

@section('title', 'Matriks Hak Akses')
@section('content')
<x-page-head
    eyebrow="Akun & Hak Akses"
    title="Matriks Hak Akses"
    description="Izin bawaan tiap peran. Centang untuk memberi akses, kosongkan untuk mencabut."
>
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Kelola Pengguna</a>
    </x-slot:actions>
</x-page-head>

<div class="panel p-5 mb-6 flex flex-wrap items-start gap-4">
    <span class="badge badge-warning shrink-0">Super Admin</span>
    <p class="text-sm text-text-muted flex-1 min-w-[16rem]">
        Selalu memiliki seluruh izin dan tidak dapat dicabut dari halaman ini, sehingga administrator platform
        tidak akan pernah terkunci dari sistem.
    </p>
</div>

@if(! $editable)
    <div class="panel p-4 mb-6" role="status">
        <p class="text-sm text-text-muted">
            Anda dapat melihat matriks ini, tetapi hanya Super Admin yang boleh mengubahnya.
        </p>
    </div>
@endif

<form method="POST" action="{{ route('permissions.update') }}" class="pb-28" data-busy novalidate>
    @csrf
    @method('PUT')

    <div class="space-y-6">
        @foreach($groups as $group)
            <x-panel class="overflow-hidden">
                <div class="px-5 py-4 border-b border-border">
                    <h2 class="font-display text-base font-bold text-text">{{ $group['label'] }}</h2>
                    <p class="mt-0.5 text-sm text-text-muted">{{ $group['description'] }}</p>
                </div>

                <div class="table-wrap">
                    <table class="table min-w-[640px]">
                        <thead>
                            <tr>
                                <th>Izin</th>
                                @foreach($roles as $roleValue => $roleName)
                                    <th class="w-32 text-center">{{ $roleName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($group['permissions'] as $row)
                                <tr>
                                    <td>
                                        <span class="font-medium">{{ $row['label'] }}</span>
                                        <span class="block text-xs text-text-muted">{{ $row['name'] }}</span>
                                    </td>
                                    @foreach($roles as $roleValue => $roleName)
                                        @php
                                            $default = in_array($roleValue, $row['defaults'], true);
                                            $fallback = ($row['granted'][$roleValue] ?? false) ? [$row['name']] : [];
                                            $granted = in_array($row['name'], (array) old("grants.$roleValue", $fallback), true);
                                        @endphp
                                        <td class="text-center">
                                            <label class="inline-flex justify-center cursor-pointer min-h-[44px] min-w-[44px]">
                                                <span class="switch">
                                                    <input type="checkbox" name="grants[{{ $roleValue }}][]"
                                                        value="{{ $row['name'] }}" @checked($granted)
                                                        @disabled(! $editable)>
                                                    <span class="switch__track" aria-hidden="true"></span>
                                                </span>
                                                <span class="sr-only">{{ $roleName }} - {{ $row['label'] }}</span>
                                            </label>
                                            @if($editable && $granted !== $default)
                                                <span class="block text-[10px] uppercase tracking-wide text-text-muted">
                                                    {{ $granted ? 'ditambahkan' : 'dicabut' }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        @endforeach
    </div>

    @if($editable)
        <div class="sticky bottom-0 mt-6 panel p-4 flex flex-wrap items-center gap-3">
            <button type="submit" class="btn btn-primary">Simpan Matriks</button>
            <p class="text-xs text-text-muted flex-1 min-w-[12rem]">
                Perubahan berlaku untuk seluruh pengguna dengan peran terkait pada permintaan berikutnya.
            </p>
        </div>
    @endif
</form>
@endsection
