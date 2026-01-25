<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;

class SchedulePattern extends Model
{
    use SoftDeletes, BelongsToTenant, Blameable;

    protected $guarded = ['id'];

    public function details(): HasMany
    {
        return $this->hasMany(SchedulePatternDetail::class)->orderBy('day_index');
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeScheduleAssignment::class, 'schedule_pattern_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->name . " ($this->cycle_length Hari)";
    }
}
