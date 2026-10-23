<?php

namespace Shipbytes\UiKit\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A user model hiding the two-factor columns, for the doctor's hidden-columns
 * row. A $hidden property, not Laravel 13's #[Hidden] attribute: Laravel 12
 * has no such attribute and would hide nothing.
 */
class HidesTwoFactorUser extends Authenticatable
{
    protected $table = 'users';

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
}
