@php
    $editing = isset($user) && $user !== null;
    $roleLocked = $roleLocked ?? false;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <label for="name" class="label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}"
            class="input @error('name') border-danger @enderror" @error('name') aria-invalid="true" @enderror>
        @error('name')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="label">Email <span class="text-danger">*</span></label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}"
            class="input @error('email') border-danger @enderror" @error('email') aria-invalid="true" @enderror>
        <p class="mt-1.5 text-xs text-text-muted">Dipakai untuk masuk. Harus unik di seluruh aplikasi.</p>
        @error('email')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>
</div>

@if($showTenantField)
    <div>
        <label for="tenant_id" class="label">Sekolah</label>
        <select id="tenant_id" name="tenant_id"
            class="input @error('tenant_id') border-danger @enderror" @error('tenant_id') aria-invalid="true" @enderror>
            <option value="">Tanpa sekolah (administrator platform)</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $user->tenant_id ?? '') === (string) $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-text-muted">Wajib diisi kecuali peran Super Admin, yang boleh oversee seluruh sekolah.</p>
        @error('tenant_id')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>
@else
    <div>
        <span class="label">Sekolah</span>
        <p class="input bg-surface text-text-muted cursor-not-allowed" aria-disabled="true">{{ auth()->user()->tenant?->name ?? 'Platform' }}</p>
        <p class="mt-1.5 text-xs text-text-muted">Pengguna baru otomatis ditempatkan di sekolah Anda.</p>
    </div>
@endif

@if($roleLocked)
    <div>
        <span class="label">Peran</span>
        <p class="flex items-center gap-2">
            <span class="badge">{{ roleLabel($user->role) }}</span>
            <span class="text-xs text-text-muted">Peran ini tidak dapat diubah dari sini.</span>
        </p>
    </div>
@else
    <div>
        <label for="role" class="label">Peran <span class="text-danger">*</span></label>
        <select id="role" name="role"
            class="input @error('role') border-danger @enderror" @error('role') aria-invalid="true" @enderror>
            <option value="">-- Pilih --</option>
            @foreach($assignableRoles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <label for="password" class="label">Kata Sandi @if(! $editing)<span class="text-danger">*</span>@endif</label>
        <input type="password" id="password" name="password" autocomplete="new-password"
            class="input @error('password') border-danger @enderror" @error('password') aria-invalid="true" @enderror>
        <p class="mt-1.5 text-xs text-text-muted">
            {{ $editing ? 'Kosongkan bila tidak ingin mengganti kata sandi.' : 'Minimal 8 karakter.' }}
        </p>
        @error('password')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="label">Ulangi Kata Sandi</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="input">
    </div>
</div>
