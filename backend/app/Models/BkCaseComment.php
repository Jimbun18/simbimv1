<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkCaseComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bk_counseling_case_id',
        'user_id',
        'teacher_id',
        'komentar',
    ];

    public function counselingCase(): BelongsTo
    {
        return $this->belongsTo(BkCounselingCase::class, 'bk_counseling_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
