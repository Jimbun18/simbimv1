<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'aksi',
        'modul',
        'target_type',
        'target_id',
        'data_sebelum',
        'data_sesudah',
        'ip',
        'user_agent',
        'keterangan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
