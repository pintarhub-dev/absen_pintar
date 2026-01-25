<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;

class EnsureTenantSetup
{
    public function handle(Request $request, Closure $next)
    {
        // Pastikan user sedang login & berada di panel Admin
        if (auth()->check() && Filament::getCurrentPanel()?->getId() === 'admin') {
            $user = auth()->user();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Setup Tenant Required'], 403);
            }
            // Jika dia Tenant Owner TAPI belum punya Tenant ID
            // DAN dia tidak sedang berada di halaman Logout (biar bisa keluar)
            // DAN dia bukan Superadmin (Superadmin bebas)
            if (
                $user->role === 'tenant_owner' &&
                is_null($user->tenant_id) &&
                $user->role !== 'superadmin' &&
                ! $request->routeIs('filament.admin.auth.logout')
            ) {
                return redirect()->route('onboarding.tenant.create');
            }
        }

        return $next($request);
    }
}
