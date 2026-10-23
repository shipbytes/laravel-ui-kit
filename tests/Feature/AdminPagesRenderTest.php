<?php

namespace Shipbytes\UiKit\Tests\Feature;

use App\Livewire\Admin\Changelog\ChangelogForm;
use App\Livewire\Admin\Contacts\ContactList;
use App\Livewire\Admin\Users\UserList;
use App\Models\ChangelogEntry;
use App\Models\ContactSubmission;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\Livewire;
use Mews\Purifier\PurifierServiceProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Shipbytes\UiKit\Tests\Concerns\AssertsRendersCleanly;
use Shipbytes\UiKit\Tests\Concerns\InstallsKitIntoSkeleton;
use Shipbytes\UiKit\Tests\TestCase;
use Spatie\Activitylog\ActivitylogServiceProvider;

/**
 * The nine admin screens, rendered over HTTP in a skeleton with every module
 * that has one installed, and seeded so each screen has rows to draw.
 *
 * admin-middleware and impersonation add no admin screen. The first would also
 * move the admin gate onto spatie/laravel-permission roles, which is the
 * fresh-app CI job's to cover, not this one's.
 *
 * Separate processes because spatie/laravel-activitylog's migrations are NAMED
 * classes. Each test publishes them again under a new timestamp, and requiring
 * the second copy in the same process redeclares CreateActivityLogTable.
 */
#[RunTestsInSeparateProcesses]
class AdminPagesRenderTest extends TestCase
{
    use AssertsRendersCleanly;
    use InstallsKitIntoSkeleton;

    private const MODULES = 'profile,support-tickets,contacts,changelog,activity-log';

    private User $admin;

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            FortifyServiceProvider::class,
            ActivitylogServiceProvider::class,
            PurifierServiceProvider::class,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->installKit(self::MODULES);

        $this->admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $this->cleanSkeleton();

        parent::tearDown();
    }

    public function test_every_admin_screen_renders(): void
    {
        ['ticket' => $ticket, 'entry' => $entry, 'user' => $user] = $this->seedScreens();

        $screens = [
            '/admin' => ['Dashboard', 'Total users', 'Joined in the last 7 days'],
            '/admin/users' => ['Users', 'Search users', 'Pending'],
            "/admin/users/{$user->id}" => [$user->name, 'Email verified', 'All users'],
            '/admin/support' => ['Support Tickets', 'Printer is on fire', 'In progress'],
            "/admin/support/{$ticket->ulid}" => ['Printer is on fire', 'Conversation', 'Admin', 'Reply to User'],
            '/admin/contacts' => ['Contact Messages', 'Select all', 'Select Partnership enquiry', 'Unread'],
            '/admin/changelog' => ['Changelog', 'New Entry', 'Dark mode', 'Published', 'Draft'],
            '/admin/changelog/create' => ['New Changelog Entry', 'Publish Date', 'role="switch"'],
            "/admin/changelog/{$entry->ulid}/edit" => ['Edit Changelog Entry', 'Dark mode'],
            '/admin/activity' => ['Activity Log', 'Changed the status', 'SupportTicket#'.$ticket->id],
        ];

        foreach ($screens as $path => $expected) {
            $response = $this->actingAs($this->admin)->get($path);

            $this->assertSame(200, $response->status(), "{$path} answered {$response->status()}");
            $html = (string) $response->getContent();

            foreach (['<h1', ...$expected] as $text) {
                $this->assertStringContainsString($text, $html, "{$path} does not show: {$text}");
            }

            $this->assertRendersCleanly($response);
        }
    }

    public function test_paginated_screens_page_through_livewire_not_links(): void
    {
        $this->seedScreens();

        // 31 users against paginate(25): two pages.
        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('wire:click="gotoPage(2, \'page\')"', false)
            ->assertSee('wire:click="nextPage(\'page\')"', false)
            ->assertDontSee('?page=2', false);
    }

    public function test_the_body_help_is_escaped_exactly_once(): void
    {
        // Text, not markup: the reader sees the tags, and they are not
        // double-escaped into a visible "&lt;p&gt;".
        $this->actingAs($this->admin)->get('/admin/changelog/create')
            ->assertOk()
            ->assertSee('You can use basic HTML: &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a href=&quot;...&quot;&gt;, &lt;h3&gt;, &lt;h4&gt;', false)
            ->assertDontSee('&amp;lt;', false);
    }

    public function test_the_contact_modal_opens_and_closes_through_show_modal(): void
    {
        ['contacts' => [$first, $second]] = $this->seedScreens();

        $this->actingAs($this->admin);

        $list = Livewire::test(ContactList::class)
            // The modal reads the server flag through an entangled Alpine ref,
            // and a close that starts in the browser calls closeModal().
            ->assertSeeHtml("x-data=\"{ showModal: \$wire.entangle('showModal') }\"")
            ->assertSeeHtml('$wire.closeModal()')
            ->assertSeeHtml('x-show="showModal"')
            ->assertSet('showModal', false)
            ->call('openModal', $first->id)
            ->assertSet('showModal', true)
            ->assertSet('viewingId', $first->id)
            ->assertSee($first->message);

        $this->assertSame('read', $first->fresh()->status, 'opening an unread message marks it read');

        $list->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('viewingId', null)
            ->call('openModal', $second->id)
            ->assertSet('viewingId', $second->id)
            ->assertSee($second->message)
            ->assertDontSee($first->message);
    }

    public function test_the_changelog_switch_saves_is_published(): void
    {
        ['entry' => $entry] = $this->seedScreens();

        $this->actingAs($this->admin);
        $this->assertFalse($entry->is_published);

        Livewire::test(ChangelogForm::class, ['ulid' => $entry->ulid])
            ->assertSeeHtml('wire:model="is_published"')
            // No `name` on the switch, so no hidden "0" to fight wire:model.
            ->assertDontSeeHtml('value="0"')
            ->set('is_published', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($entry->fresh()->is_published);

        Livewire::test(ChangelogForm::class, ['ulid' => $entry->ulid])
            ->assertSet('is_published', true)
            ->set('is_published', false)
            ->call('save');

        $this->assertFalse($entry->fresh()->is_published);
    }

    public function test_searching_the_user_list_returns_to_page_one(): void
    {
        $this->seedScreens();

        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('search', 'Needle')
            ->assertSet('paginators.page', 1)
            ->assertSee('needle@example.com')
            ->assertDontSee('member7@example.com');
    }

    /**
     * @return array{user: User, ticket: SupportTicket, entry: ChangelogEntry, contacts: array<int, ContactSubmission>}
     */
    private function seedScreens(): array
    {
        $user = User::forceCreate([
            'name' => 'Needle Person',
            'email' => 'needle@example.com',
            'password' => bcrypt('secret'),
        ]);

        // With the admin and the needle, 31 users: two pages of 25.
        foreach (range(1, 29) as $i) {
            User::forceCreate([
                'name' => "Member {$i}",
                'email' => "member{$i}@example.com",
                'password' => bcrypt('secret'),
                'email_verified_at' => $i % 2 ? now() : null,
            ]);
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => 'Printer is on fire',
            'category' => 'bug',
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'message' => 'It is still burning.', 'is_admin_reply' => false]);
        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $this->admin->id, 'message' => 'We have sent a fire extinguisher.', 'is_admin_reply' => true]);

        SupportTicket::create(['user_id' => $user->id, 'subject' => 'Invoice question', 'category' => 'billing', 'priority' => 'low', 'status' => 'open']);

        $contacts = [
            ContactSubmission::create(['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Partnership enquiry', 'message' => 'We would like to partner with you.', 'status' => 'unread']),
            ContactSubmission::create(['name' => 'Grace', 'email' => 'needle@example.com', 'subject' => 'Bug report', 'message' => 'The export button does nothing.', 'status' => 'read']),
        ];

        ContactSubmission::create(['name' => 'Linus', 'email' => 'linus@example.com', 'subject' => 'Thanks', 'message' => 'Great product.', 'status' => 'replied', 'reply' => 'Thank you!', 'replied_at' => now()]);

        $entry = ChangelogEntry::create(['title' => 'Dark mode', 'body' => '<p>Everything flips.</p>', 'category' => 'feature', 'published_at' => now()->toDateString(), 'is_published' => false]);
        ChangelogEntry::create(['title' => 'Faster exports', 'body' => '<p>Twice as fast.</p>', 'category' => 'improvement', 'published_at' => now()->subDay()->toDateString(), 'is_published' => true]);

        activity()->causedBy($this->admin)->performedOn($ticket)->event('updated')->log('Changed the status');
        activity()->log('Nightly cleanup ran');

        return ['user' => $user, 'ticket' => $ticket, 'entry' => $entry, 'contacts' => $contacts];
    }
}
