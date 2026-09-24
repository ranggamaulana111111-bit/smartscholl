<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $groups = collect(config('school-settings'))
            ->filter(
                fn (array $cfg): bool => in_array($user->role, $cfg['roles'] ?? ['super_admin', 'admin_sekolah'], true)
            )
            ->mapWithKeys(function (array $cfg, string $group) {
                $tenantId = (bool) $cfg['tenant_scoped'] ? $this->targetTenantId() : null;

                $values = Setting::withoutGlobalScopes()
                    ->where('group', $group)
                    ->where('tenant_id', $tenantId)
                    ->pluck('value', 'key');

                $fields = [];

                foreach ($cfg['fields'] as $name => $field) {
                    $fields[$name] = [
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'options' => $field['options'] ?? [],
                        'placeholder' => $field['placeholder'] ?? '',
                        'hint' => $field['hint'] ?? '',
                        'value' => $values[$name] ?? $field['default'],
                    ];
                }

                return [$group => [
                    'label' => $cfg['label'],
                    'description' => $cfg['description'] ?? '',
                    'tenant_scoped' => (bool) $cfg['tenant_scoped'],
                    'fields' => $fields,
                ]];
            })
            ->all();

        return view('settings.index', compact('groups'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $group = $request->string('group')->toString();
        $tenantScoped = (bool) config("school-settings.$group.tenant_scoped", false);
        $tenantId = $tenantScoped ? $this->targetTenantId() : null;
        $fields = config("school-settings.$group.fields", []);

        $previous = Setting::withoutGlobalScopes()
            ->where('group', $group)
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key');

        $old = [];
        $new = [];
        $writing = null;

        foreach ($fields as $name => $cfg) {
            $value = $cfg['type'] === 'toggle'
                ? ($request->boolean($name) ? '1' : '0')
                : (string) $request->input($name, '');

            if (($previous[$name] ?? null) !== $value) {
                $old[$name] = $previous[$name] ?? null;
                $new[$name] = $value;
            }

            $writing = Setting::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'group' => $group, 'key' => $name],
                ['tenant_id' => $tenantId, 'group' => $group, 'key' => $name, 'value' => $value]
            );
        }

        if ($new !== []) {
            log_audit('update_settings', $writing, $old, $new);
        }

        $label = (string) config("school-settings.$group.label", $group);
        $message = $new === []
            ? 'Tidak ada perubahan pengaturan.'
            : "Pengaturan {$label} berhasil disimpan.";

        return redirect(route('settings.index').'#'.$group)->with('success', $message);
    }

    private function targetTenantId(): ?string
    {
        return currentTenantId() ?: Tenant::query()
            ->where('status', 'active')
            ->orderBy('created_at')
            ->value('id');
    }
}
