<?php

namespace Shipbytes\UiKit\Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait AssertsRendersCleanly
{
    /**
     * What every rendered page must not contain, whatever page it is.
     */
    protected function assertRendersCleanly(TestResponse|string $response): void
    {
        $html = is_string($response) ? $response : (string) $response->getContent();

        // Blade prints a component tag it failed to compile as literal text,
        // so this is the signature of a broken ds:: call.
        $this->assertStringNotContainsString('<x-ds::', $html, 'an uncompiled ds:: component tag reached the page');
        $this->assertStringNotContainsString('<x-ui-kit::', $html, 'an uncompiled ui-kit:: component tag reached the page');

        // Livewire wraps @if blocks in <!--[if BLOCK]> markers and must keep
        // them out of opening tags. A marker inside one renders the tag's ">"
        // as text: blade-ui 1.1's buttons all read "> Sign in" because a
        // comment in the button named a tag. Fixed upstream in 1.2.0.
        $found = preg_match('/<([a-zA-Z][\w:.-]*)[^<>]*<!--\[if BLOCK\]>/', $html, $match);

        $this->assertSame(0, $found, $found
            ? "a Livewire morph marker sits inside an opening <{$match[1]}> tag, so its \">\" renders as text:\n  "
                .substr(preg_replace('/\s+/', ' ', $match[0]), 0, 200)
            : '');
    }
}
