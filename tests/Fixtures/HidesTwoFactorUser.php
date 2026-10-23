<?php

namespace Shipbytes\UiKit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A Laravel 13 style model hiding the two-factor columns through the
 * attribute, for the doctor's hidden-columns row.
 */
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class HidesTwoFactorUser extends Authenticatable
{
    protected $table = 'users';
}
