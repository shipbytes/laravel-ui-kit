<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use Shipbytes\UiKit\Support\UiKit;

/**
 * Laravel finds this by name (App\Models\SupportTicket → App\Policies\
 * SupportTicketPolicy), so nothing registers it. A ticket belongs to the user
 * who opened it; an admin can see and change every one.
 */
class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $ticket->isOwnedBy($user) || UiKit::isAdmin($user);
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $ticket->isOwnedBy($user) || UiKit::isAdmin($user);
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return UiKit::isAdmin($user);
    }
}
