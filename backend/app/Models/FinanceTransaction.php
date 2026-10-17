<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_transactions';

    protected $fillable = [
        'no_transaksi',
        'tipe',
        'kategori',
        'jumlah',
        'tanggal',
        'keterangan',
        'payment_id',
        'lampiran_path',
        'is_pembalik',
        'referensi_transaksi_id',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'jumlah' => 'float',
            'is_pembalik' => 'boolean',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public static function generateTransactionNumber(string $tipe): string
    {
        $code = $tipe === 'masuk' ? 'IN' : 'OUT';
        $prefix = "TRX/{$code}/" . now()->format('Y/m') . '/';
        $last = static::where('no_transaksi', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('/', $last->no_transaksi);
            $seq = (int) end($parts) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
