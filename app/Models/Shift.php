<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;

class Shift extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTenant, Blameable;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'is_day_off',
        'is_flexible',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'daily_target_minutes',
        'late_tolerance_minutes',
    ];

    // Kebutuhan Flexible Shift
    protected $casts = [
        'is_flexible' => 'boolean',
    ];

    public function getLabelAttribute(): string
    {
        $text = $this->name;

        if ($this->is_day_off) {
            return $text . ' (LIBUR / OFF)';
        }

        if ($this->is_flexible) {
            return $text . ' (' . $this->daily_target_minutes . ' minutes)';
        }

        if ($this->start_time && $this->end_time) {
            // Potong detik (08:00:00 -> 08:00) biar rapi
            $start = substr($this->start_time, 0, 5);
            $end = substr($this->end_time, 0, 5);
            return $text . " ($start - $end)";
        }

        return $text;
    }
}
