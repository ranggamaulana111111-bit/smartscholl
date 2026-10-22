@extends('layouts.app')

@section('title', 'Pengaturan')
@section('content')
<x-page-head
    eyebrow="Konfigurasi"
    title="Pengaturan"
    description="Kelola identitas sekolah, aturan penilaian, ambang peringatan dini, perilaku absensi, dan konfigurasi global aplikasi."
></x-page-head>

<div class="max-w-5xl">
    @if($needsTenant)
        <div class="panel p-6 mb-8 border-l-4 border-l-accent" role="status">
            <p class="font-display text-lg font-semibold text-text">Pilih sekolah terlebih dahulu</p>
            <p class="text-sm text-text-muted mt-1 max-w-2xl">
                Pengaturan profil sekolah, penilaian, peringatan dini, dan kehadiran disimpan per sekolah.
                Akun Super Admin tidak terikat pada satu sekolah, jadi sekolah tujuan harus dipilih
                eksplisit sebelum nilainya bisa dibaca atau disimpan.
            </p>
            <form method="GET" action="{{ route('settings.index') }}" class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                <div class="flex-1">
                    <label for="tenant-picker" class="label">Sekolah</label>
                    <select id="tenant-picker" name="tenant" class="input">
                        <option value="">Pilih sekolah...</option>
                        @foreach($tenants as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary shrink-0">Buka Pengaturan</button>
            </form>
            @if($tenants->isEmpty())
                <p class="text-sm text-danger mt-3">Belum ada sekolah berstatus aktif untuk diatur.</p>
            @endif
        </div>
    @elseif($tenants->isNotEmpty())
        <form method="GET" action="{{ route('settings.index') }}" class="mb-8 flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
            <div class="flex-1">
                <label for="tenant-picker" class="label">Sekolah yang diatur</label>
                <select id="tenant-picker" name="tenant" class="input" onchange="this.form.submit()">
                    @foreach($tenants as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $tenantId)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="btn btn-ghost shrink-0">Ganti Sekolah</button></noscript>
        </form>
    @endif

    <div class="flex flex-wrap gap-2 mb-8" role="tablist" aria-label="Grup pengaturan">
        @foreach($groups as $key => $group)
            <button type="button" role="tab" id="tab-{{ $key }}" data-settings-tab="{{ $key }}"
                aria-controls="panel-{{ $key }}"
                class="px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors duration-fast {{ $loop->first ? 'bg-primary text-accent' : 'bg-surface text-text-muted hover:bg-background' }}"
                @if($loop->first) aria-selected="true" @else tabindex="-1" @endif>
                {{ $group['label'] }}
            </button>
        @endforeach
    </div>

    @foreach($groups as $key => $group)
        <div class="panel p-6 sm:p-8 mb-8" id="panel-{{ $key }}" data-settings-panel="{{ $key }}" role="tabpanel"
            @if(!$loop->first) hidden @endif aria-labelledby="tab-{{ $key }}">
            <div class="mb-6">
                <h2 class="font-display text-xl font-bold text-text">{{ $group['label'] }}</h2>
                <p class="mt-1 text-sm text-text-muted">{{ $group['description'] }}</p>
            </div>

            <p class="mb-6 text-xs text-text-muted">
                {{ $group['tenant_scoped']
                    ? 'Berlaku untuk sekolah: '.($tenantName ?? 'sekolah yang dipilih')
                    : 'Konfigurasi global aplikasi, hanya untuk Super Admin.' }}
            </p>

            @if($group['tenant_scoped'] && ! $tenantId)
                <div class="mb-6 rounded-lg border border-border bg-surface p-5">
                    <p class="font-semibold text-text text-sm">Nilai belum ditampilkan</p>
                    <p class="text-sm text-text-muted mt-1 max-w-2xl">
                        Group ini disimpan per sekolah. Pilih sekolah di atas untuk melihat dan mengubah nilainya.
                    </p>
                </div>
            @else
            <form method="POST" action="{{ route('settings.update') }}"
                class="space-y-6 {{ $key === 'sekolah' ? 'max-w-4xl' : 'max-w-2xl' }}" novalidate>
                @csrf
                <input type="hidden" name="group" value="{{ $key }}">
                @if($group['tenant_scoped'] && ! auth()->user()->tenant_id)
                    <input type="hidden" name="tenant" value="{{ $tenantId }}">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($group['fields'] as $name => $field)
                        @if($field['type'] === 'textarea')
                            <div class="sm:col-span-2">
                        @else
                            <div>
                        @endif
                            @if($field['type'] === 'toggle')
                                <label for="field-{{ $key }}-{{ $name }}" class="flex items-center justify-between gap-4 cursor-pointer">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-text">{{ $field['label'] }}</span>
                                        @if($field['hint'])
                                            <span class="block text-xs text-text-muted">{{ $field['hint'] }}</span>
                                        @endif
                                    </span>
                                    <span class="switch shrink-0">
                                        <input type="checkbox" id="field-{{ $key }}-{{ $name }}" name="{{ $name }}" value="1"
                                            @checked($field['value'] === '1' || $field['value'] === true)
                                            @error($name) aria-invalid="true" @enderror>
                                        <span class="switch__track" aria-hidden="true"></span>
                                    </span>
                                </label>
                            @elseif($field['type'] === 'select')
                                <label for="field-{{ $key }}-{{ $name }}" class="label">{{ $field['label'] }}</label>
                                <select id="field-{{ $key }}-{{ $name }}" name="{{ $name }}"
                                    class="input @error($name) border-danger @enderror" @error($name) aria-invalid="true" @enderror>
                                    @foreach($field['options'] as $optValue => $optLabel)
                                        <option value="{{ $optValue }}" @selected(old($name, $field['value']) == $optValue)>{{ $optLabel }}</option>
                                    @endforeach
                                </select>
                            @else
                                <label for="field-{{ $key }}-{{ $name }}" class="label">{{ $field['label'] }}</label>
                                <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" id="field-{{ $key }}-{{ $name }}"
                                    name="{{ $name }}" value="{{ old($name, $field['value']) }}" placeholder="{{ $field['placeholder'] }}"
                                    @if($field['type'] === 'number') min="0" @endif
                                    class="input @error($name) border-danger @enderror" @error($name) aria-invalid="true" @enderror>
                            @endif

                            @if($field['type'] !== 'toggle' && $field['hint'])
                                <p class="mt-1 text-xs text-text-muted">{{ $field['hint'] }}</p>
                            @endif
                            @error($name)
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-border">
                    <button type="submit" class="btn btn-primary">Simpan {{ $group['label'] }}</button>
                </div>
            </form>
            @endif
        </div>
    @endforeach
</div>

<script>
    (function () {
        const tabs = Array.from(document.querySelectorAll('[data-settings-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-settings-panel]'));
        if (tabs.length === 0) return;

        const select = function (name) {
            const exists = tabs.some(function (t) { return t.dataset.settingsTab === name; });
            if (!exists) name = tabs[0].dataset.settingsTab;

            tabs.forEach(function (tab) {
                const active = tab.dataset.settingsTab === name;
                tab.classList.toggle('bg-primary', active);
                tab.classList.toggle('text-accent', active);
                tab.classList.toggle('bg-surface', !active);
                tab.classList.toggle('text-text-muted', !active);
                tab.classList.toggle('hover:bg-background', !active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
            });

            panels.forEach(function (panel) {
                panel.hidden = panel.dataset.settingsPanel !== name;
            });
        };

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () {
                select(tab.dataset.settingsTab);
            });
            tab.addEventListener('keydown', function (e) {
                const len = tabs.length;
                let move = -1;
                if (e.key === 'ArrowRight') move = 1;
                else if (e.key === 'ArrowLeft') move = -1;
                else if (e.key === 'Home') move = -index;
                else if (e.key === 'End') move = len - 1 - index;
                if (move !== -1) {
                    e.preventDefault();
                    const next = tabs[(index + move + len) % len];
                    select(next.dataset.settingsTab);
                    next.focus();
                }
            });
        });

        const initial = (window.location.hash || '').replace('#', '');
        select(initial || tabs[0].dataset.settingsTab);
    })();
</script>
@endsection