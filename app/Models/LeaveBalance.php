<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;

class LeaveBalance extends Model
{
    use HasFactory;
    use BelongsToTenant, Blameable;

    protected $guarded = ['id'];

    protected $fillable = [
        'tenant_id ',
        'employee_id',
        'leave_type_id',
        'year',
        'entitlement',
        'carried_over',
        'taken'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
