<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'no_tagihan',
        'student_id',
        'fee_type_id',
        'academic_year_id',
        'periode',
        'jumlah',
        'jumlah_dibayar',
        'sisa',
        'status',
        'jatuh_tempo',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'jumlah_dibayar' => 'float',
            'sisa' => 'float',
            'jatuh_tempo' => 'date:Y-m-d',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function generateNumber(?int $seqNumber = null): string
    {
        $prefix = 'INV/' . now()->format('Y/m') . '/';

        if ($seqNumber !== null) {
            return $prefix . str_pad((string) $seqNumber, 4, '0', STR_PAD_LEFT);
        }

        $last = static::where('no_tagihan', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('/', $last->no_tagihan);
            $seq = (int) end($parts) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
