<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChangelogEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'title',
        'body',
        'category',
        'published_at',
        'is_published',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = Str::ulid()->toString();
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now()->toDateString());
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'feature' => 'New Feature',
            'improvement' => 'Improvement',
            'fix' => 'Bug Fix',
            'other' => 'Other',
            default => ucfirst((string) $this->category),
        };
    }
}
