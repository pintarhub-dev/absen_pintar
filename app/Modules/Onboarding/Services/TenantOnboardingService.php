<?php

namespace App\Modules\Onboarding\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    public function createTenant(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {

            $tenantId = DB::table('tenants')->insertGetId([
                'name' => $data['name'],
                'code' => $data['code'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'slug' => Str::slug($data['name']),
                'referral' => Str::random(5),
                'subscription_plan' => 'free',
                'status' => 'active',
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update([
                'tenant_id' => $tenantId,
                'full_name' => $data['full_name'],
                'updated_by' => $user->id,
                'updated_at' => now(),
            ]);

            return $tenantId;
        });
    }
}
