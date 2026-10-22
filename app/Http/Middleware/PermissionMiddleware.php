<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Gate halus yang setara dengan `role:`, tetapi memeriksa izin spesifik.
     * Beberapa izin dipisah tanda koma bila salah satu sudah cukup (semantik OR).
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Akses ditolak.');
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Izin tidak mencukupi.');
    }
}
