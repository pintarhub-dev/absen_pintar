<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blameable;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;
    use Blameable;
    protected $guarded = ['id'];

    // Relasi ke User (Owner & Admin)
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Relasi ke Karyawan
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
