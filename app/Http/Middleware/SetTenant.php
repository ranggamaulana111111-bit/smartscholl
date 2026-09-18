<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $tenantId = Auth::user()->tenant_id;

            if ($tenantId) {
                $tenant = Tenant::where('id', $tenantId)
                    ->where('status', 'active')
                    ->first();

                if (! $tenant) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => 'Akun sekolah Anda tidak aktif.',
                    ]);
                }

                app()->instance('currentTenant', $tenant);
            }
        }

        return $next($request);
    }
}
