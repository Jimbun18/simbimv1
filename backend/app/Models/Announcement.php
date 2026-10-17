<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'announcements';

    protected $fillable = [
        'judul',
        'isi',
        'kategori',
        'target_role',
        'lampiran_path',
        'tgl_terbit',
        'tgl_berakhir',
        'is_aktif',
        'is_pinned',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_terbit' => 'date:Y-m-d',
            'tgl_berakhir' => 'date:Y-m-d',
            'is_aktif' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
