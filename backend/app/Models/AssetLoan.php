<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLoan extends Model
{
    use HasFactory;

    protected $table = 'asset_loans';

    protected $fillable = [
        'asset_id',
        'peminjam_nama',
        'peminjam_user_id',
        'tgl_pinjam',
        'tgl_rencana_kembali',
        'tgl_kembali_aktual',
        'status',
        'keperluan',
        'kondisi_sebelum',
        'kondisi_sesudah',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pinjam' => 'date:Y-m-d',
            'tgl_rencana_kembali' => 'date:Y-m-d',
            'tgl_kembali_aktual' => 'date:Y-m-d',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peminjam_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
