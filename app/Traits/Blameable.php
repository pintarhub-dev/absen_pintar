<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait Blameable
{
    public static function bootBlameable()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                if (Schema::hasColumn($model->getTable(), 'created_by')) {
                    $model->forceFill(['created_by' => Auth::id()]);
                }
                if (Schema::hasColumn($model->getTable(), 'updated_by')) {
                    $model->forceFill(['updated_by' => Auth::id()]);
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                if (Schema::hasColumn($model->getTable(), 'updated_by')) {
                    $model->forceFill(['updated_by' => Auth::id()]);
                }
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                // Pastikan kolom ada sebelum diisi (menghindari error di tabel yg tidak punya deleted_by)
                if (Schema::hasColumn($model->getTable(), 'deleted_by')) {
                    $model->forceFill(['deleted_by' => Auth::id()]);
                    // Perlu save manual karena event deleting terjadi sebelum soft delete
                    // Gunakan saveQuietly agar tidak memicu event 'updating' lagi (loop)
                    $model->saveQuietly();
                }
            }
        });
    }
}
