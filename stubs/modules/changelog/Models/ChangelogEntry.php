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

    /**
     * The categories, key => label, in the order the public page's filter
     * lists them.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'feature' => 'New Feature',
            'improvement' => 'Improvement',
            'fix' => 'Bug Fix',
            'other' => 'Other',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::categories()[$this->category] ?? ucfirst((string) $this->category);
    }

    /**
     * The badge tone for the category, from the design system's five. Beside
     * category_label, so a new category gets its words and its tone together.
     */
    public function categoryTone(): string
    {
        return match ($this->category) {
            'feature' => 'accent',
            'improvement' => 'warning',
            'fix' => 'success',
            default => 'neutral',
        };
    }
}
