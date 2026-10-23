<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'user_id',
        'subject',
        'category',
        'priority',
        'status',
        'ip_address',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = Str::ulid()->toString();
            }
            if (empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * The categories a user picks from, key => label. The create form, its
     * validation and the admin filter all read this list.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'general' => 'General',
            'bug' => 'Bug',
            'feature_request' => 'Feature Request',
            'billing' => 'Billing',
            'other' => 'Other',
        ];
    }

    public function categoryLabel(): string
    {
        return static::categories()[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }

    public static function statusLabelFor(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    public function statusLabel(): string
    {
        return static::statusLabelFor((string) $this->status);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Compared as strings, so an integer key and a driver that hands the
     * column back as a string still agree, and so do UUID keys.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (string) $this->user_id === (string) $user->getKey();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function close(): void
    {
        $this->status = 'closed';
        $this->closed_at = now();
        $this->save();
    }

    public function reopen(): void
    {
        $this->status = 'open';
        $this->closed_at = null;
        $this->save();
    }

    /**
     * The badge tone for the status, from the design system's five. Kept here
     * so the list and the detail page cannot drift apart.
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'open' => 'accent',
            'in_progress' => 'warning',
            'resolved' => 'success',
            default => 'neutral',
        };
    }

    public function priorityTone(): string
    {
        return match ($this->priority) {
            'high' => 'danger',
            'medium' => 'accent',
            default => 'neutral',
        };
    }
}
