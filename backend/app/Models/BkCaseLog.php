<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkCaseLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bk_counseling_case_id',
        'tanggal',
        'tahap',
        'sumber_data',
        'observasi_temuan',
        'telaah_bk',
        'kebutuhan_tujuan',
        'tindak_lanjut',
        'hasil_evaluasi',
        'perubahan_pola',
        'status',
        'catatan_konteks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function counselingCase(): BelongsTo
    {
        return $this->belongsTo(BkCounselingCase::class, 'bk_counseling_case_id');
    }
}

