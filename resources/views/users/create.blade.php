@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('content')
<x-page-head
    eyebrow="Akun & Hak Akses"
    title="Tambah Pengguna"
    description="Buat akun baru dan tentukan peran serta sekolahnya."
>
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('users.store') }}" class="max-w-3xl panel p-6 sm:p-8 space-y-6" data-busy novalidate>
    @csrf

    @include('users._form')

    <div class="flex items-center gap-3 pt-2 border-t border-border">
        <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
        <a href="{{ route('users.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
