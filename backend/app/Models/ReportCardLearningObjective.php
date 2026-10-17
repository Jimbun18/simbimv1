<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardLearningObjective extends Model
{
    use HasFactory;

    protected $table = 'report_card_learning_objectives';

    protected $fillable = [
        'subject_id',
        'nama_mapel',
        'tingkat',
        'semester',
        'kode_tp',
        'deskripsi_tp',
        'created_by',
        'updated_by',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
