<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTenant, Blameable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_carry_forward' => 'boolean',
        'min_months_of_service' => 'integer',
        'quota' => 'integer',
    ];

    // Relasi: Satu jenis cuti bisa punya banyak request
    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->code
            ? "$this->code ~ {$this->name}"
            : $this->name;
    }
}
