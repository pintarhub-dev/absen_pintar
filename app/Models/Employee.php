<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;
use App\Traits\Blameable;

class Employee extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTenant, Blameable;

    protected $guarded = ['id'];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'work_location_id',
        'is_flexible_location',
        'registered_device_id',
        'nik',
        'full_name',
        'nickname',
        'place_of_birth',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'identity_number',
        'job_title',
        'department',
        'join_date',
        'employment_status',
        'is_access_web',
        'is_attendance_required',
        'resignation_date',
        'resignation_note',
        'employee_id_supervisor',
        'employee_id_manager',
    ];

    protected $casts = [
        'is_access_web' => 'integer',
        'is_attendance_required' => 'integer',
        'employee_id_supervisor' => 'integer',
        'employee_id_manager' => 'integer',
        'join_date' => 'date',
        'resignation_date' => 'date',
    ];

    // Relasi ke Akun Login
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke quota cuti karyawan
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'employee_id');
    }

    // Mengetahui supervisor atau atasannya siapa?
    public function atasan()
    {
        return $this->belongsTo(Employee::class, 'employee_id_supervisor');
    }

    // Mengetahui manager atau atasan diatasnya siapa?
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'employee_id_manager');
    }

    // Siapa bawahan langsung (subordinate)
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'employee_id_supervisor');
    }

    // Siapa bawahan manager
    public function managedSubordinates()
    {
        return $this->hasMany(Employee::class, 'employee_id_manager');
    }

    // Relasi ke History Penugasan Jadwal
    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(EmployeeScheduleAssignment::class);
    }

    // Relasi ke Data Absensi (Attendance Summaries)
    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->nik
            ? "$this->nik ~ {$this->full_name}"
            : $this->full_name;
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }
}
