<?php

namespace Shipbytes\UiKit\Tests\Feature\Admin;

use App\Models\ContactSubmission;
use App\Models\SupportTicket;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class AdminShellTest extends InstalledKitTestCase
{
    protected string $modules = 'profile,support-tickets,contacts';

    public function test_the_phone_sheet_carries_profile_back_to_site_and_log_out(): void
    {
        $html = (string) $this->actingAs($this->makeAdmin())->get('/admin')->assertOk()->getContent();

        // Everything after the More sheet opens.
        $sheet = substr($html, strpos($html, 'aria-label="More"'));

        $this->assertStringContainsString('href="'.route('profile').'"', $sheet);
        $this->assertStringContainsString('Back to site', $sheet);
        $this->assertMatchesRegularExpression('#<form method="POST" action="'.preg_quote(route('logout'), '#').'">.*?<button[^>]*type="submit"[^>]*>.*?Log out#s', $sheet);
    }

    public function test_badges_count_open_tickets_and_unread_messages_on_the_rail_and_the_phone_bar(): void
    {
        $admin = $this->makeAdmin();

        foreach (range(1, 12) as $i) {
            SupportTicket::create(['user_id' => $admin->id, 'subject' => "Ticket {$i}", 'category' => 'bug', 'priority' => 'low', 'status' => 'open']);
        }
        SupportTicket::create(['user_id' => $admin->id, 'subject' => 'Done', 'category' => 'bug', 'priority' => 'low', 'status' => 'closed']);
        ContactSubmission::create(['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Hi', 'message' => 'Hello there.', 'status' => 'unread']);
        ContactSubmission::create(['name' => 'Old', 'email' => 'old@example.com', 'subject' => 'Hi', 'message' => 'Archived.', 'status' => 'unread', 'archived_at' => now()]);

        $this->assertSame(['open_tickets' => 12, 'unread_contacts' => 1], app(SidebarBadgeResolver::class)->counts());

        $response = $this->actingAs($admin)->get('/admin')->assertOk();
        $this->assertRendersCleanly($response);

        // The phone bar: Dashboard, Users, Support. The pill caps, the name does not.
        $response->assertSee('9+')->assertSee(', 12 new');
    }

    public function test_the_user_shell_links_to_the_profile(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/profile')
            ->assertOk()
            ->assertSee('aria-label="Profile"', false)
            ->assertSee('href="'.route('profile').'"', false);
    }
}
