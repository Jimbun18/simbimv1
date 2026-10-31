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

    protected $appends = [
        'action',
        'description',
        'ip_address',
        'waktu_formatted',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getActionAttribute(): ?string
    {
        return $this->aksi;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->keterangan;
    }

    public function getIpAddressAttribute(): ?string
    {
        return $this->ip;
    }

    public function getWaktuFormattedAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->translatedFormat('d M Y, H:i') . ' WIB' : '-';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
