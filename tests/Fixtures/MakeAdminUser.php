<?php

namespace Shipbytes\UiKit\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Stand-in for App\Models\User in ui-kit:make-admin tests (the Testbench
 * skeleton ships no autoloadable User model).
 */
class MakeAdminUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
