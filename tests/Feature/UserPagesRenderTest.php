<?php

namespace Shipbytes\UiKit\Tests\Feature;

use App\Models\ChangelogEntry;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Mews\Purifier\PurifierServiceProvider;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * The pages the modules add outside the admin panel, rendered over HTTP in the
 * user shell, and the shell's header for a guest and for a signed-in user.
 */
class UserPagesRenderTest extends InstalledKitTestCase
{
    protected string $modules = 'profile,support-tickets,contacts,changelog';

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [PurifierServiceProvider::class]);
    }

    public function test_every_user_facing_page_renders(): void
    {
        $user = $this->makeUser(['name' => 'Olive Owner']);
        $ticket = SupportTicket::create(['user_id' => $user->id, 'subject' => 'Printer is on fire', 'category' => 'bug', 'priority' => 'high', 'status' => 'in_progress']);
        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'message' => 'It is still burning.', 'is_admin_reply' => false]);
        ChangelogEntry::create(['title' => 'Dark mode', 'body' => '<p>Everything flips.</p>', 'category' => 'feature', 'published_at' => now()->subDay()->toDateString(), 'is_published' => true]);

        $pages = [
            '/support' => ['Support', 'Printer is on fire', 'In progress', 'New ticket'],
            '/support/create' => ['New ticket', 'Subject', 'Category', 'Message', 'Open ticket'],
            "/support/{$ticket->ulid}" => ['Printer is on fire', 'Conversation', 'It is still burning.', 'Add a reply'],
            '/contact' => ['Contact us', 'Subject', 'Send message'],
            '/changelog' => ['Changelog', 'Dark mode', 'Everything flips.', 'New Feature'],
        ];

        foreach ($pages as $path => $expected) {
            $response = $this->actingAs($user)->get($path);

            $this->assertSame(200, $response->status(), "{$path} answered {$response->status()}");

            foreach (['<h1', ...$expected] as $text) {
                $this->assertStringContainsString($text, (string) $response->getContent(), "{$path} does not show: {$text}");
            }

            $this->assertRendersCleanly($response);
        }
    }

    public function test_a_guest_gets_the_signed_out_header(): void
    {
        foreach (['/contact', '/changelog'] as $path) {
            $html = (string) $this->get($path)->assertOk()->getContent();
            $this->assertRendersCleanly($html);

            $this->assertMatchesRegularExpression('/href="'.preg_quote(route('login'), '/').'"[^>]*>\s*Sign in\s*</', $html, $path);
            $this->assertStringContainsString('href="'.route('register').'"', $html, $path);
            $this->assertStringNotContainsString('Log out', $html, $path);
            $this->assertStringNotContainsString('aria-label="Profile"', $html, $path);

            // Support needs an account, so a guest is not offered it.
            $this->assertStringNotContainsString('href="'.route('support.index').'"', $html, $path);
            $this->assertStringContainsString('href="'.route('changelog.public').'"', $html, $path);
        }
    }

    public function test_a_signed_in_user_gets_the_account_header_and_the_module_links(): void
    {
        $html = (string) $this->actingAs($this->makeUser())->get('/changelog')->assertOk()->getContent();

        $this->assertStringContainsString('Log out', $html);
        $this->assertStringContainsString('aria-label="Profile"', $html);
        $this->assertStringNotContainsString('href="'.route('login').'"', $html);
        $this->assertMatchesRegularExpression('/<nav aria-label="Main"[^>]*>.*href="'.preg_quote(route('support.index'), '/').'".*href="'.preg_quote(route('changelog.public'), '/').'"[^>]*aria-current="page"/s', $html);
    }
}
