<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'ip_address',
        'reply',
        'replied_at',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * What the sidebar badge counts: unread and still in the inbox.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('archived_at')->where('status', 'unread');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * The badge tone for the status, from the design system's five. Kept here
     * so the table row and the open message cannot drift apart.
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'unread' => 'accent',
            'replied' => 'success',
            default => 'neutral',
        };
    }
}
