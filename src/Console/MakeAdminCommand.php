<?php

namespace Shipbytes\UiKit\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class MakeAdminCommand extends Command
{
    protected $signature = 'ui-kit:make-admin
                            {email? : Email of the user to promote (defaults to the first user)}';

    protected $description = 'Grant a user admin access — assigns the admin role and/or sets the is_admin flag.';

    public function handle(): int
    {
        /** @var class-string<Model> $model */
        $model = (string) config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($model)) {
            $this->error("User model {$model} not found (config auth.providers.users.model).");

            return self::FAILURE;
        }

        $email = $this->argument('email');

        $user = $email !== null
            ? $model::query()->where('email', $email)->first()
            : $model::query()->orderBy((new $model)->getKeyName())->first();

        if ($user === null) {
            $this->error($email !== null
                ? "No user found with email {$email}."
                : 'No users exist yet — register one first, then re-run this command.');

            return self::FAILURE;
        }

        $granted = [];

        // With admin-middleware installed, admin access is the Spatie role.
        if (in_array('admin-middleware', config('ui-kit.installed_modules', []), true)
            && method_exists($user, 'assignRole')) {
            $role = 'Spatie\\Permission\\Models\\Role';

            if (class_exists($role)) {
                $role::findOrCreate('admin', (string) config('auth.defaults.guard', 'web'));
            }

            $user->assignRole('admin');
            $granted[] = "role 'admin'";
        }

        // The core fallback middleware checks the is_admin column — set it too
        // when present, so access doesn't hinge on which middleware is active.
        if (Schema::hasColumn($user->getTable(), 'is_admin')) {
            $user->forceFill(['is_admin' => true])->save();
            $granted[] = 'is_admin flag';
        }

        if ($granted === []) {
            $this->error('No admin mechanism found — expected an is_admin column or the admin-middleware module. Run ui-kit:install (or ui-kit:doctor) first.');

            return self::FAILURE;
        }

        $this->info(sprintf('%s is now an admin (%s).', (string) $user->getAttribute('email'), implode(' + ', $granted)));

        return self::SUCCESS;
    }
}
