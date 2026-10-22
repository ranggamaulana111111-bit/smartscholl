@php
    $linkedAccount = $account ?? null;
    $accountOpen = (bool) old('create_account');
@endphp

@if(auth()->user()->hasPermission('users.manage'))
    <fieldset id="student-account" class="space-y-4 rounded-lg border border-border p-4">
        <legend class="sr-only">Akun Login</legend>

        <label for="create_account" class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" id="create_account" name="create_account" value="1" class="mt-1" @checked($accountOpen)>
            <span>
                <span class="block text-sm font-semibold text-text">
                    {{ $linkedAccount ? 'Kelola akun login siswa ini' : 'Buat akun login untuk siswa ini' }}
                </span>
                <span class="block text-xs text-text-muted">
                    @if($linkedAccount)
                        Akun saat ini <strong class="font-mono font-normal text-text">{{ $linkedAccount->email }}</strong>.
                        Kosongkan kata sandi bila tidak ingin menggantinya.
                    @else
                        Siswa dapat masuk memakai email dan kata sandi ini untuk melihat absensi dan nilainya sendiri.
                    @endif
                </span>
            </span>
        </label>
        @error('create_account')
            <p class="error-text">{{ $message }}</p>
        @enderror

        <div id="account-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-2" @hidden(! $accountOpen)>
            <div class="sm:col-span-2">
                <label for="account_email" class="label">Email {{ $linkedAccount ? '' : '*' }}</label>
                <input type="email" id="account_email" name="account_email" value="{{ old('account_email', $linkedAccount?->email) }}"
                    autocomplete="off" placeholder="nama.siswa@sekolah.sch.id"
                    class="input @error('account_email') border-danger @enderror" @error('account_email') aria-invalid="true" @enderror>
                <p class="mt-1.5 text-xs text-text-muted">Harus unik di seluruh aplikasi.</p>
                @error('account_email')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="account_password" class="label">Kata Sandi {{ $linkedAccount ? '' : '*' }}</label>
                <input type="password" id="account_password" name="account_password" autocomplete="new-password"
                    class="input @error('account_password') border-danger @enderror" @error('account_password') aria-invalid="true" @enderror>
                <p class="mt-1.5 text-xs text-text-muted">
                    {{ $linkedAccount ? 'Kosongkan bila tidak ingin mengganti.' : 'Minimal 8 karakter.' }}
                </p>
                @error('account_password')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="account_password_confirmation" class="label">Ulangi Kata Sandi</label>
                <input type="password" id="account_password_confirmation" name="account_password_confirmation" autocomplete="new-password" class="input">
            </div>
        </div>
    </fieldset>

    <script>
        (function () {
            var toggle = document.getElementById('create_account');
            var fields = document.getElementById('account-fields');

            if (!toggle || !fields) {
                return;
            }

            function sync() {
                fields.hidden = !toggle.checked;
            }

            toggle.addEventListener('change', sync);

            if (window.location.hash === '#student-account') {
                toggle.checked = true;
            }

            sync();
        })();
    </script>
@endif
