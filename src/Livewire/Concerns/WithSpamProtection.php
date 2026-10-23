<?php

namespace Shipbytes\UiKit\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * The two guards in front of every form the kit's modules open to the world
 * (the contact form, a new ticket, a ticket reply): a honeypot field and an
 * hourly limit. The form renders the field with ui-kit::honeypot.
 *
 * Order matters, and each form does it the same way:
 *
 *   1. caughtInHoneypot(): fake success and save nothing
 *   2. validate()
 *   3. passesRateLimit(): only now, so a typo never uses up an attempt
 *   4. save, then mail
 */
trait WithSpamProtection
{
    /**
     * The honeypot. A person never sees or reaches it (visually hidden, out of
     * the tab order, no autofill); a bot filling every field fills this one.
     */
    public string $website = '';

    protected function caughtInHoneypot(): bool
    {
        return $this->website !== '';
    }

    /**
     * Count one submission against $key, or refuse when $maxAttempts are used
     * up in the window: the refusal goes into the `limit` error, which the
     * form shows above its fields, and this returns false.
     */
    protected function passesRateLimit(string $key, int $maxAttempts, int $decaySeconds = 3600): bool
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            $this->addError('limit', "That's the limit for now. Please try again in {$minutes} ".Str::plural('minute', $minutes).'.');

            return false;
        }

        RateLimiter::hit($key, $decaySeconds);

        return true;
    }
}
