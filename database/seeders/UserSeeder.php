<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            [
                'tenant_id' => 1,
                'full_name' => 'Super Admin',
                'role' => 'superadmin',
                'email' => 'superadmin@gmail.com',
                'password' => Hash::make('12345678'),
                'status' => 'active'
            ],
            [
                'tenant_id' => 2,
                'full_name' => 'Emilly Kie',
                'role' => 'tenant_owner',
                'email' => 'emillykie@gmail.com',
                'password' => Hash::make('12345678'),
                'status' => 'active'
            ],
        ]);
    }
}
