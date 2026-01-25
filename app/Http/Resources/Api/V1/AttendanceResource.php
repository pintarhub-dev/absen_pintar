<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'shift' => [
                'name' => $this->shift?->name ?? 'Off/Libur',
                'schedule_in' => $this->schedule_in,
                'schedule_out' => $this->schedule_out,
            ],
            'clock_in' => [
                'time' => $this->clock_in,
                'latitude' => $this->clock_in_latitude,
                'longitude' => $this->clock_in_longitude,
                'status' => $this->status, // present/late
            ],
            'clock_out' => [
                'time' => $this->clock_out,
                'latitude' => $this->clock_out_latitude,
                'longitude' => $this->clock_out_longitude,
            ],
            // Hitung durasi kerja real-time jika sudah clock out
            'work_duration' => $this->clock_out
                ? \Carbon\Carbon::parse($this->clock_in)->diffInHours($this->clock_out) . ' Jam'
                : null,
        ];
    }
}
