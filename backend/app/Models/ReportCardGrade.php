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
        'nilai_uh',
        'rata_uh',
        'nilai_sas',
        'nilai_akhir',
        'predikat',
        'capaian_kompetensi',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'nilai_uh' => 'array',
            'rata_uh' => 'float',
            'nilai_sas' => 'float',
            'nilai_akhir' => 'float',
            'urutan' => 'integer',
        ];
    }

    /**
     * Hitung Nilai Rapor (NR) dengan rumus resmi:
     * NR = (3 * RUH + 2 * PSAS) / 5
     */
    public static function calculateNR(?float $ruh, ?float $psas): ?float
    {
        if ($ruh !== null && $psas !== null) {
            return round((3 * $ruh + 2 * $psas) / 5);
        }
        if ($ruh !== null) {
            return round($ruh);
        }
        if ($psas !== null) {
            return round($psas);
        }
        return null;
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
