<?php

namespace Shipbytes\UiKit\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A User model without roles: admins are the is_admin column, as in a kit
 * app without the admin-middleware module.
 */
class FlagAdminUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
