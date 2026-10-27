<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsArticle extends Model
{
    use HasFactory;

    protected $table = 'cms_articles';

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'created_by',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            return '';
        }
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }
        return '/storage/' . ltrim($this->image_path, '/');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
