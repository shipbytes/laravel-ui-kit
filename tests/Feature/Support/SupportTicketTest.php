<?php

namespace Shipbytes\UiKit\Tests\Feature\Support;

use App\Livewire\Support\CreateTicket;
use App\Livewire\Support\TicketDetail;
use App\Livewire\Support\TicketList;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * The user's side of the support-tickets module: their list, a new ticket,
 * and the conversation on one, driven the way the user drives them.
 */
class SupportTicketTest extends InstalledKitTestCase
{
    protected string $modules = 'support-tickets';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->owner = $this->makeUser(['name' => 'Olive Owner']);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $ticket = $this->ticketFor($this->owner);

        $this->get('/support')->assertRedirect('/login');
        $this->get('/support/create')->assertRedirect('/login');
        $this->get("/support/{$ticket->ulid}")->assertRedirect('/login');
    }

    public function test_the_list_shows_only_my_own_tickets(): void
    {
        $this->ticketFor($this->owner, ['subject' => 'My printer is jammed']);
        $this->ticketFor($this->makeUser(), ['subject' => 'Someone else entirely']);

        $this->actingAs($this->owner)->get('/support')
            ->assertOk()
            ->assertSee('My printer is jammed')
            ->assertDontSee('Someone else entirely');
    }

    public function test_the_list_filters_by_status_and_keeps_it_in_the_url(): void
    {
        $this->ticketFor($this->owner, ['subject' => 'Still broken']);
        $this->ticketFor($this->owner, ['subject' => 'All sorted now', 'status' => 'closed']);

        Livewire::actingAs($this->owner)
            ->test(TicketList::class)
            ->assertSee('Still broken')
            ->assertSee('All sorted now')
            ->set('statusFilter', 'closed')
            ->assertSee('All sorted now')
            ->assertDontSee('Still broken')
            ->set('statusFilter', 'resolved')
            ->assertSee('No resolved tickets');

        $this->actingAs($this->owner)->get('/support?status=closed')
            ->assertSee('All sorted now')
            ->assertDontSee('Still broken');
    }

    public function test_an_empty_list_offers_a_new_ticket(): void
    {
        $this->actingAs($this->owner)->get('/support')
            ->assertOk()
            ->assertSee('No tickets yet')
            ->assertSee('href="'.route('support.create').'"', false);
    }

    public function test_the_list_pages_fifteen_at_a_time(): void
    {
        foreach (range(1, 16) as $i) {
            $this->ticketFor($this->owner, ['subject' => "Ticket number {$i}"]);
        }

        $this->actingAs($this->owner)->get('/support')
            ->assertSee('wire:click="nextPage(\'page\')"', false);
    }

    public function test_a_user_opens_a_ticket(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(CreateTicket::class)
            ->set('subject', 'Cannot download my invoice')
            ->set('category', 'billing')
            ->set('message', 'The invoice link on the billing page returns an error.')
            ->call('submit')
            ->assertHasNoErrors();

        $ticket = SupportTicket::sole();
        $component->assertRedirect(route('support.show', $ticket->ulid));

        $this->assertTrue($ticket->isOwnedBy($this->owner));
        $this->assertSame('Cannot download my invoice', $ticket->subject);
        $this->assertSame('billing', $ticket->category);
        $this->assertSame('medium', $ticket->priority);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('127.0.0.1', $ticket->ip_address);

        $first = $ticket->replies()->sole();
        $this->assertSame('The invoice link on the billing page returns an error.', $first->message);
        $this->assertFalse($first->is_admin_reply);
        $this->assertTrue($first->user->is($this->owner));
    }

    public function test_a_new_ticket_is_validated(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CreateTicket::class)
            ->call('submit')
            ->assertHasErrors(['subject' => 'required', 'category' => 'required', 'message' => 'required'])
            ->set('subject', str_repeat('a', 256))
            ->set('category', 'gossip')
            ->set('message', 'Too short.')
            ->call('submit')
            ->assertHasErrors(['subject' => 'max', 'category' => 'in', 'message' => 'min'])
            ->set('message', str_repeat('a', 5001))
            ->call('submit')
            ->assertHasErrors(['message' => 'max']);

        $this->assertSame(0, SupportTicket::count());
    }

    public function test_the_category_list_comes_from_the_model(): void
    {
        $this->actingAs($this->owner)->get('/support/create')
            ->assertOk()
            ->assertSeeInOrder(array_values(SupportTicket::categories()));
    }

    public function test_the_honeypot_fakes_success_and_saves_nothing(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CreateTicket::class)
            ->set('subject', 'Cheap watches')
            ->set('category', 'general')
            ->set('message', 'Buy cheap watches at our wonderful website today.')
            ->set('website', 'https://spam.example')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('support.index'));

        $this->assertSame(0, SupportTicket::count());
        Mail::assertNothingOutgoing();
    }

    public function test_five_tickets_an_hour_and_a_mistake_does_not_count(): void
    {
        $form = Livewire::actingAs($this->owner)->test(CreateTicket::class);

        // Failed validation never reaches the limiter.
        foreach (range(1, 6) as $i) {
            $form->set('subject', '')->call('submit')->assertHasErrors(['subject']);
        }

        foreach (range(1, 5) as $i) {
            $this->submitTicket("Ticket {$i}")->assertHasNoErrors();
        }

        $this->submitTicket('Ticket 6')->assertHasErrors(['limit']);
        $this->assertSame(5, SupportTicket::count());

        // The limit is per user.
        $this->submitTicket('Someone else', $this->makeUser())->assertHasNoErrors();
    }

    public function test_a_user_sees_their_ticket_and_its_thread(): void
    {
        $ticket = $this->ticketFor($this->owner, ['subject' => 'Export is broken']);
        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $this->makeAdmin()->id, 'message' => 'Looking into it now.', 'is_admin_reply' => true]);

        $this->actingAs($this->owner)->get("/support/{$ticket->ulid}")
            ->assertOk()
            ->assertSee('Export is broken')
            ->assertSeeInOrder(['The export button does nothing at all.', 'Support team', 'Looking into it now.'])
            ->assertSee('Close ticket');
    }

    public function test_someone_elses_ticket_is_forbidden_but_an_admin_may_look(): void
    {
        $ticket = $this->ticketFor($this->owner);

        $this->actingAs($this->makeUser())->get("/support/{$ticket->ulid}")->assertForbidden();
        $this->actingAs($this->makeAdmin())->get("/support/{$ticket->ulid}")->assertOk();
        $this->actingAs($this->owner)->get('/support/01HZZZZZZZZZZZZZZZZZZZZZZZ')->assertNotFound();
    }

    public function test_the_policy(): void
    {
        $ticket = $this->ticketFor($this->owner);
        $stranger = $this->makeUser();
        $admin = $this->makeAdmin();

        foreach (['view', 'update'] as $ability) {
            $this->assertTrue(Gate::forUser($this->owner)->allows($ability, $ticket), "owner {$ability}");
            $this->assertFalse(Gate::forUser($stranger)->allows($ability, $ticket), "stranger {$ability}");
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $ticket), "admin {$ability}");
        }

        $this->assertFalse(Gate::forUser($this->owner)->allows('delete', $ticket));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $ticket));
    }

    public function test_a_user_replies(): void
    {
        $ticket = $this->ticketFor($this->owner);

        Livewire::actingAs($this->owner)
            ->test(TicketDetail::class, ['ulid' => $ticket->ulid])
            ->set('reply', 'ok')
            ->call('submitReply')
            ->assertHasNoErrors()
            ->assertSet('reply', '')
            ->assertSee('Your reply was added.');

        $reply = $ticket->replies()->latest('id')->first();
        $this->assertSame('ok', $reply->message);
        $this->assertFalse($reply->is_admin_reply);

        Livewire::actingAs($this->owner)
            ->test(TicketDetail::class, ['ulid' => $ticket->ulid])
            ->set('reply', 'x')
            ->call('submitReply')
            ->assertHasErrors(['reply' => 'min'])
            ->set('reply', str_repeat('x', 5001))
            ->call('submitReply')
            ->assertHasErrors(['reply' => 'max']);
    }

    public function test_a_reply_is_refused_when_the_ticket_is_closed(): void
    {
        foreach (['closed', 'resolved'] as $status) {
            $ticket = $this->ticketFor($this->owner, ['status' => $status]);

            Livewire::actingAs($this->owner)
                ->test(TicketDetail::class, ['ulid' => $ticket->ulid])
                ->assertSee('Reopen it to add a reply')
                ->assertDontSee('Send reply')
                ->set('reply', 'Hello again, is anyone there?')
                ->call('submitReply')
                ->assertHasErrors(['reply']);

            $this->assertSame(1, $ticket->replies()->count(), "a {$status} ticket took a reply");
        }

        Mail::assertNothingOutgoing();
    }

    public function test_ten_replies_an_hour_on_one_ticket(): void
    {
        $ticket = $this->ticketFor($this->owner);
        $page = Livewire::actingAs($this->owner)->test(TicketDetail::class, ['ulid' => $ticket->ulid]);

        foreach (range(1, 10) as $i) {
            $page->set('reply', "Reply number {$i}")->call('submitReply')->assertHasNoErrors();
        }

        $page->set('reply', 'One too many')->call('submitReply')->assertHasErrors(['limit']);
        $this->assertSame(11, $ticket->replies()->count());

        // Another ticket has its own allowance.
        $other = $this->ticketFor($this->owner);
        Livewire::actingAs($this->owner)
            ->test(TicketDetail::class, ['ulid' => $other->ulid])
            ->set('reply', 'A different conversation')
            ->call('submitReply')
            ->assertHasNoErrors();
    }

    public function test_the_owner_closes_and_reopens(): void
    {
        $ticket = $this->ticketFor($this->owner);

        $page = Livewire::actingAs($this->owner)
            ->test(TicketDetail::class, ['ulid' => $ticket->ulid])
            ->call('closeTicket')
            ->assertSee('Reopen ticket');

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);

        $page->call('reopenTicket')->assertSee('Close ticket');

        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->closed_at);
    }

    public function test_a_stranger_cannot_reach_the_actions(): void
    {
        $ticket = $this->ticketFor($this->owner);

        Livewire::actingAs($this->makeUser())
            ->test(TicketDetail::class, ['ulid' => $ticket->ulid])
            ->assertForbidden();

        $this->assertSame('open', $ticket->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ticketFor(User $user, array $attributes = []): SupportTicket
    {
        $ticket = SupportTicket::create($attributes + [
            'user_id' => $user->id,
            'subject' => 'Export is broken',
            'category' => 'bug',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'The export button does nothing at all.',
            'is_admin_reply' => false,
        ]);

        return $ticket;
    }

    private function submitTicket(string $subject, ?User $user = null): Testable
    {
        return Livewire::actingAs($user ?? $this->owner)
            ->test(CreateTicket::class)
            ->set('subject', $subject)
            ->set('category', 'general')
            ->set('message', 'Twenty characters at the very least, as asked.')
            ->call('submit');
    }
}
