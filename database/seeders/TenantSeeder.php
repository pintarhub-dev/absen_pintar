<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::insert([
            [
                'name' => 'PT Pintar Hub',
                'slug' => 'pt-pintar-hub',
                'code' => 'KP',
                'referral' => 'OLRTY',
                'address' => 'JL. Tembusan Batu 1',
                'phone' => '081288911557',
                'subscription_plan' => 'enterprise'
            ],
            [
                'name' => 'PT Astrindo Travel Service',
                'slug' => 'pt-astrindo-travel-service',
                'code' => 'ASK',
                'referral' => 'GKJHP',
                'address' => 'Jl. Teluk Betung No.40, RT.4/RW.1, Kb. Melati, Kecamatan Tanah Abang, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10230',
                'phone' => '0213907576',
                'subscription_plan' => 'free'
            ]
        ]);
    }
}
