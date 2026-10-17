<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk mengisi created_by dan updated_by secara otomatis pada model.
 * Tambahkan ke model yang membutuhkan audit trail pembuat/pengubah.
 */
trait HasCreatedUpdatedBy
{
    public static function bootHasCreatedUpdatedBy(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && !$model->isDirty('created_by')) {
                $model->created_by = Auth::id();
            }
            if (Auth::check() && !$model->isDirty('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && !$model->isDirty('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
