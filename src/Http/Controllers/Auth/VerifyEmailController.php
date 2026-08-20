<?php

namespace Shipbytes\UiKit\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Shipbytes\UiKit\Support\UiKit;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            // Marks the email verified and fires the Verified event.
            $request->fulfill();
        }

        return redirect()->intended(UiKit::homeUrl().'?verified=1');
    }
}
