<?php

namespace App\Models\Concerns;

use Spatie\Permission\Traits\HasRoles;

/**
 * Drop this trait on your User model:
 *
 *   use App\Models\Concerns\IsAdminUser;
 *
 *   class User extends Authenticatable {
 *       use IsAdminUser;
 *   }
 *
 * It re-exports Spatie's HasRoles so the rest of the app can keep importing
 * from App\Models\Concerns without coupling to the Spatie namespace.
 */
trait IsAdminUser
{
    use HasRoles;
}
