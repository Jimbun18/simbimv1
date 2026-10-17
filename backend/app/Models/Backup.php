<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $table = 'backups';

    protected $fillable = [
        'nama_file',
        'tipe',
        'ukuran',
        'lokasi',
        'status',
        'checksum',
        'dibuat_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function getFormattedUkuranAttribute(): string
    {
        $bytes = $this->ukuran;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
