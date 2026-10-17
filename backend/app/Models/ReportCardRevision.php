<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardRevision extends Model
{
    use HasFactory;

    protected $table = 'report_card_revisions';

    protected $fillable = [
        'report_card_id',
        'version',
        'snapshot_data',
        'revised_by',
        'revised_at',
        'revision_notes',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'revised_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function revisedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
