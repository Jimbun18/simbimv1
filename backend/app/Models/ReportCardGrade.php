<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardGrade extends Model
{
    use HasFactory;

    protected $table = 'report_card_grades';

    protected $fillable = [
        'report_card_id',
        'subject_id',
        'nama_mapel',
        'kelompok',
        'tema',
        'aspek',
        'sub_aspek',
        'nilai_akhir',
        'predikat',
        'capaian_kompetensi',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'nilai_akhir' => 'float',
            'urutan' => 'integer',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
