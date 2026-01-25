<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceDetail extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function summary()
    {
        return $this->belongsTo(AttendanceSummary::class, 'attendance_summary_id');
    }

    public function location()
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }

    // Relasi ke Lokasi
    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }
}
