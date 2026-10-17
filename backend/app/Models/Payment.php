<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'no_kuitansi',
        'invoice_id',
        'tanggal',
        'jumlah',
        'metode',
        'bukti_path',
        'catatan',
        'is_batal',
        'alasan_batal',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'jumlah' => 'float',
            'is_batal' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(FinanceTransaction::class);
    }

    public static function generateReceiptNumber(): string
    {
        $prefix = 'KWI/' . now()->format('Y/m') . '/';
        $last = static::where('no_kuitansi', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('/', $last->no_kuitansi);
            $seq = (int) end($parts) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
