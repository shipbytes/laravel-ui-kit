<?php

namespace Shipbytes\UiKit\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class UiKit
{
    /**
     * URL of the consumer's public home page.
     *
     * Uses the route named in ui-kit.brand.home_route when it exists; falls
     * back to the app root so fresh installs (which have no named "home"
     * route) never throw a RouteNotFoundException.
     */
    public static function homeUrl(): string
    {
        $route = config('ui-kit.brand.home_route');

        if (is_string($route) && $route !== '' && Route::has($route)) {
            return route($route, absolute: false);
        }

        return '/';
    }

    /**
     * A user's photo URL for ds::avatar's `src`, or null for the initials.
     * avatarUrl() is generated into the UiKitUser trait by the profile module,
     * so without that module every user gets initials.
     */
    public static function avatarUrl(?object $user): ?string
    {
        return $user !== null && method_exists($user, 'avatarUrl') ? $user->avatarUrl() : null;
    }

    /**
     * Whether a user holds admin access: the Spatie role when the
     * admin-middleware module put HasRoles on the model, else the is_admin
     * column the core fallback middleware reads. Either one counts, the same
     * as the two ways ui-kit:make-admin grants it.
     */
    public static function isAdmin(?object $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (method_exists($user, 'hasRole') && $user->hasRole('admin'))
            || (bool) ($user->is_admin ?? false);
    }

    /**
     * Who gets the mail meant for the site's admins (a new ticket, a contact
     * form message): `ui-kit.mail.admin_addresses` when it lists any, else
     * every user isAdmin() would let into the panel's queue. That is the
     * Spatie `admin` role when the User model has hasRole() (the
     * admin-middleware module), else the is_admin column.
     *
     * @return Collection<int, object|string> users, or plain addresses
     */
    public static function admins(): Collection
    {
        $addresses = array_values(array_filter((array) config('ui-kit.mail.admin_addresses', [])));

        if ($addresses !== []) {
            return collect($addresses);
        }

        $model = (string) config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($model)) {
            return collect();
        }

        if (method_exists($model, 'hasRole')) {
            try {
                return $model::role('admin')->get();
            } catch (\Throwable $e) {
                // Spatie throws when the role was never seeded: no admins yet.
                Log::warning('ui-kit: could not list admins by role', ['error' => $e->getMessage()]);

                return collect();
            }
        }

        return $model::query()->where('is_admin', true)->get();
    }

    /**
     * Send a mailable to each recipient, one message apiece, and never throw.
     * The modules call this AFTER saving the row it is about, so a mail
     * server that is down, a missing MAIL_FROM_ADDRESS or a bad address costs
     * the email and never the ticket or the message. The kit's mailables are
     * ShouldQueue, so on a real queue this only hands them over; a send that
     * fails in the worker lands in failed_jobs instead.
     *
     * Each failure is logged as "ui-kit: could not send <class>".
     *
     * @param  iterable<int, object|string>|object|string  $recipients  users or addresses
     * @return \Throwable|null the first failure, or null when every send was handed off
     */
    public static function sendMail(iterable|object|string $recipients, Mailable $mailable): ?\Throwable
    {
        $failure = null;

        foreach (is_iterable($recipients) ? $recipients : [$recipients] as $recipient) {
            try {
                // A clone apiece: to() adds to a mailable's list rather than
                // replacing it, so reusing one would mail every admin so far.
                Mail::to($recipient)->send(clone $mailable);
            } catch (\Throwable $e) {
                $failure ??= $e;

                Log::error('ui-kit: could not send '.$mailable::class, ['error' => $e->getMessage()]);
            }
        }

        return $failure;
    }
}
