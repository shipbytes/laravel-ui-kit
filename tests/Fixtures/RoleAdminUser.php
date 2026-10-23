<?php

namespace Shipbytes\UiKit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A User model shaped like one with Spatie's HasRoles, which the package does
 * not depend on: a hasRole() method and a role() scope, over a plain column.
 */
class RoleAdminUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }
}
