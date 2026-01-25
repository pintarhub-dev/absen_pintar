<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Shift::insert([
            [
                'tenant_id' => 2,
                'name' => 'Shift 1 Pagi',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'is_day_off' => 0,
                'is_flexible' => 0,
                'daily_target_minutes' => null,
                'break_duration_minutes' => 60,
                'late_tolerance_minutes' => 10,
            ],
            [
                'tenant_id' => 2,
                'name' => 'Shift 2 Pagi',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'is_day_off' => 0,
                'is_flexible' => 0,
                'daily_target_minutes' => null,
                'break_duration_minutes' => 60,
                'late_tolerance_minutes' => 10,
            ],
            [
                'tenant_id' => 2,
                'name' => 'Shift Flexible',
                'start_time' => null,
                'end_time' => null,
                'is_day_off' => 0,
                'is_flexible' => 1,
                'daily_target_minutes' => 480,
                'break_duration_minutes' => 0,
                'late_tolerance_minutes' => 0,
            ],
            [
                'tenant_id' => 2,
                'name' => 'Libur',
                'start_time' => null,
                'end_time' => null,
                'is_day_off' => 1,
                'is_flexible' => 0,
                'daily_target_minutes' => null,
                'break_duration_minutes' => 0,
                'late_tolerance_minutes' => 0,
            ],
        ]);
    }
}
