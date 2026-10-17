<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeRange extends Model
{
    use HasFactory;

    protected $table = 'ref_income_ranges';

    protected $fillable = [
        'nama',
        'urutan',
    ];
}
