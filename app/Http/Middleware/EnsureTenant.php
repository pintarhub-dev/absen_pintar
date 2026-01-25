<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            app()->instance('tenant_id', auth()->user()->tenant_id);
        }

        return $next($request);
    }
}
