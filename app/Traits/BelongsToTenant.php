<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant()
    {
        // 1. GLOBAL SCOPE (Untuk READ/SELECT data)
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = null;

            // A. Cek Service Container (Prioritas Utama - dari Middleware)
            // Pakai 'bound' agar tidak error jika belum di-set
            if (app()->bound('tenant_id')) {
                $tenantId = app('tenant_id');
            }

            // B. Cek Auth (Plan B)
            // HANYA JIKA $tenantId masih kosong DAN Model yang dipanggil BUKAN User
            // Syarat "!($builder->getModel() instanceof \App\Models\User)" ini WAJIB ada agar tidak loop/layar putih
            if (! $tenantId && !($builder->getModel() instanceof \App\Models\User)) {
                if (Auth::check()) {
                    $tenantId = Auth::user()->tenant_id;
                }
            }

            // C. Terapkan Filter
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        // 2. CREATING OBSERVER (Untuk CREATE/INSERT data)
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                // Cek Container
                if (app()->bound('tenant_id')) {
                    $model->tenant_id = app('tenant_id');
                }
                // Cek Auth (Aman dilakukan di 'creating' karena tidak memicu query loop)
                elseif (Auth::check()) {
                    $model->tenant_id = Auth::user()->tenant_id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
