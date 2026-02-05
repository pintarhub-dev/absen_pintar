<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Traits\Blameable;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Filament\Models\Contracts\HasAvatar;

class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail, HasAvatar
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Blameable;

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        return null;
    }

    protected static function booted(): void
    {
        static::updating(function ($user) {
            // Cek apakah kolom avatar berubah (isDirty)
            if ($user->isDirty('avatar')) {
                // Ambil path foto lama (getOriginal)
                $oldAvatar = $user->getOriginal('avatar');
                // Jika ada foto lama, dan filenya beneran ada di storage
                if ($oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
                    Storage::disk('public')->delete($oldAvatar);
                }
            }
        });

        static::deleting(function ($user) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->employee?->delete();
        });
    }

    protected $guarded = ['id'];

    protected $fillable = [
        'tenant_id',
        'full_name',
        'role',
        'email',
        'password',
        'fcm_token',
        'status',
        'avatar'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'otp_expires_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Helper untuk cek apakah user ini super admin
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Biarkan Middleware EnsureTenantSetup yang menangani redirect-nya.
        // SYARAT 1: Akun Wajib Active
        if ($this->status !== 'active') {
            return false;
        }

        // 2. JALUR VIP: Superadmin & Tenant Owner SELALU BOLEH
        if ($this->role === 'superadmin' || $this->role === 'tenant_owner') {
            return true;
        }

        // 3. JALUR KARYAWAN KHUSUS (HRD)
        // Jika role employee, CEK data employeenya, apakah is_access_web = true?
        if ($this->role === 'employee' && $this->employee) {
            return $this->employee->is_access_web;
        }

        // Role Employee gak bisa masuk mari
        // Web hanya untuk: Superadmin, Tenant Owner, Employee tapi yang IS HR diset TRUE
        return false;
    }

    public function getFilamentName(): string
    {
        return $this->full_name ?? $this->email;
    }
}
