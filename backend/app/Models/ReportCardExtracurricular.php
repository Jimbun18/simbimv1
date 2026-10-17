<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardExtracurricular extends Model
{
    use HasFactory;

    protected $table = 'report_card_extracurriculars';

    protected $fillable = [
        'report_card_id',
        'kegiatan',
        'predikat',
        'keterangan',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }
}
