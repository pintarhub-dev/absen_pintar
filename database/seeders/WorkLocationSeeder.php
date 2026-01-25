<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkLocation;

class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WorkLocation::insert([
            [
                'tenant_id' => '2',
                'name' => 'Astrindo HO',
                'address' => 'Jl. Teluk Betung No.40, RT.4/RW.1, Kb. Melati, Kecamatan Tanah Abang, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10230',
                'latitude' => '-6.196422639987634',
                'longitude' => '106.82161240968269',
                'radius' => '50',
                'timezone' => 'Asia/Jakarta'
            ],
            [
                'tenant_id' => '2',
                'name' => 'Jakarta Petojo',
                'address' => 'NO Jl. Balikpapan No.15A 10, RT.10/RW.6, Petojo Sel., Kecamatan Gambir, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10130',
                'latitude' => '-6.170677427038055',
                'longitude' => '106.81263802317402',
                'radius' => '50',
                'timezone' => 'Asia/Jakarta'
            ]
        ]);
    }
}
