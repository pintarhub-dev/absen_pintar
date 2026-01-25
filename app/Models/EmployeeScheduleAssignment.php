<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;
use App\Traits\HasSchedulePattern;

class EmployeeScheduleAssignment extends Model
{
    use BelongsToTenant, Blameable, SoftDeletes;
    use HasSchedulePattern;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
        'pattern_start_day_index' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedulePattern(): BelongsTo
    {
        return $this->belongsTo(SchedulePattern::class, 'schedule_pattern_id');
    }

    public function hasattendance()
    {
        return $this->hasMany(AttendanceSummary::class, 'employee_id');
    }
}
