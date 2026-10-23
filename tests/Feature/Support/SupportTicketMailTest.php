<?php

namespace Shipbytes\UiKit\Tests\Feature\Support;

use App\Livewire\Admin\Support\TicketDetail as AdminTicketDetail;
use App\Livewire\Support\CreateTicket;
use App\Livewire\Support\TicketDetail;
use App\Mail\NewTicketMail;
use App\Mail\TicketReplyMail;
use App\Mail\TicketStatusChangedMail;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * Who each support mail goes to, what it says, and that a mail which cannot be
 * sent costs the email and never the ticket.
 */
class SupportTicketMailTest extends InstalledKitTestCase
{
    protected string $modules = 'support-tickets';

    private User $owner;

    private User $admin;

    private User $secondAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser(['name' => 'Olive Owner', 'email' => 'olive@example.com']);
        $this->admin = $this->makeAdmin(['name' => 'Ada Admin', 'email' => 'ada@example.com']);
        $this->secondAdmin = $this->makeAdmin(['name' => 'Alan Admin', 'email' => 'alan@example.com']);
    }

    public function test_a_new_ticket_mails_each_admin_and_replies_go_to_the_user(): void
    {
        Mail::fake();

        $this->openTicket();
        $ticket = SupportTicket::sole();

        Mail::assertQueuedCount(2);

        foreach ([$this->admin, $this->secondAdmin] as $admin) {
            Mail::assertQueued(NewTicketMail::class, fn (NewTicketMail $mail) => $mail->hasTo($admin->email)
                && ! $mail->hasTo($this->owner->email)
                && $mail->hasReplyTo('olive@example.com', 'Olive Owner')
                && $mail->ticket->is($ticket));
        }

        Mail::assertNotQueued(NewTicketMail::class, fn (NewTicketMail $mail) => $mail->hasTo($this->owner->email));
    }

    public function test_configured_admin_addresses_replace_the_admin_users(): void
    {
        Mail::fake();
        config(['ui-kit.mail.admin_addresses' => ['desk@example.com']]);

        $this->openTicket();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(NewTicketMail::class, fn (NewTicketMail $mail) => $mail->hasTo('desk@example.com'));
    }

    public function test_the_new_ticket_mail(): void
    {
        $this->openTicket();
        $ticket = SupportTicket::sole();
        $mail = new NewTicketMail($ticket);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
        $this->assertNull($mail->queue, 'the default queue, not a named one');

        $mail->assertHasSubject('[Support] New ticket: Cannot download my invoice');
        $mail->assertSeeInHtml('Olive Owner');
        $mail->assertSeeInHtml('Billing');
        $mail->assertSeeInHtml('The invoice link on the billing page returns an error.');
        $mail->assertSeeInHtml(route('admin.support.show', $ticket->ulid));
        $mail->assertSeeInText('> The invoice link on the billing page returns an error.');
    }

    public function test_a_user_reply_mails_the_admins_with_the_admin_link(): void
    {
        $ticket = $this->ticket();
        Mail::fake();

        Livewire::actingAs($this->owner)
            ->test(TicketDetail::class, ['ulid' => $ticket->ulid])
            ->set('reply', 'Here is the screenshot you asked for.')
            ->call('submitReply')
            ->assertHasNoErrors();

        Mail::assertQueuedCount(2);
        Mail::assertQueued(TicketReplyMail::class, fn (TicketReplyMail $mail) => $mail->hasTo($this->admin->email) && $mail->toAdmin);

        $mail = new TicketReplyMail($ticket, $ticket->replies()->reorder('id', 'desc')->first(), toAdmin: true);
        $mail->assertHasSubject('[Support] New reply: Export is broken');
        $mail->assertHasReplyTo('olive@example.com');
        $mail->assertSeeInHtml('Here is the screenshot you asked for.');
        $mail->assertSeeInHtml(route('admin.support.show', $ticket->ulid));
        $mail->assertDontSeeInHtml(route('support.show', $ticket->ulid).'"');
    }

    public function test_an_admin_reply_mails_the_owner_with_their_own_link(): void
    {
        $ticket = $this->ticket();
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(AdminTicketDetail::class, ['ulid' => $ticket->ulid])
            ->set('reply', 'We have fixed the export, please try again.')
            ->call('submitReply')
            ->assertHasNoErrors()
            ->assertSee('Reply sent to olive@example.com.');

        Mail::assertQueuedCount(1);
        Mail::assertQueued(TicketReplyMail::class, fn (TicketReplyMail $mail) => $mail->hasTo('olive@example.com') && ! $mail->toAdmin);

        $mail = new TicketReplyMail($ticket, $ticket->replies()->reorder('id', 'desc')->first(), toAdmin: false);
        $mail->assertHasSubject('New reply to your ticket: Export is broken');
        $mail->assertSeeInHtml('Hi Olive Owner');
        $mail->assertSeeInHtml('We have fixed the export, please try again.');
        $mail->assertSeeInHtml(route('support.show', $ticket->ulid));
        $mail->assertDontSeeInHtml(route('admin.support.show', $ticket->ulid));
    }

    public function test_a_status_change_mails_the_owner_old_to_new(): void
    {
        $ticket = $this->ticket();
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(AdminTicketDetail::class, ['ulid' => $ticket->ulid])
            ->set('newStatus', 'in_progress')
            ->call('updateStatus')
            ->assertHasNoErrors();

        Mail::assertQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail) => $mail->hasTo('olive@example.com')
            && $mail->oldStatus === 'open'
            && $mail->newStatus === 'in_progress');

        $mail = new TicketStatusChangedMail($ticket, 'open', 'in_progress');
        $mail->assertHasSubject('Your ticket is in progress: Export is broken');
        $mail->assertSeeInOrderInHtml(['Open', 'In progress']);
        $mail->assertSeeInHtml(route('support.show', $ticket->ulid));
        $mail->assertDontSeeInHtml('reopen');

        (new TicketStatusChangedMail($ticket, 'in_progress', 'resolved'))->assertSeeInHtml('reopen it from the ticket page');

        // Choosing the status it already has sends nothing.
        Mail::fake();
        Livewire::actingAs($this->admin)
            ->test(AdminTicketDetail::class, ['ulid' => $ticket->ulid])
            ->call('updateStatus');
        Mail::assertNothingOutgoing();
    }

    public function test_a_mail_that_cannot_be_sent_never_loses_the_ticket(): void
    {
        $this->breakTheMailer();
        Log::spy();

        $this->openTicket()->assertHasNoErrors();

        $ticket = SupportTicket::sole();
        $this->assertSame(1, $ticket->replies()->count());

        Log::shouldHaveReceived('error')->withArgs(fn (string $message) => $message === 'ui-kit: could not send App\Mail\NewTicketMail')->twice();

        // The admin side says so rather than claiming the mail went.
        Livewire::actingAs($this->admin)
            ->test(AdminTicketDetail::class, ['ulid' => $ticket->ulid])
            ->set('reply', 'We are on it and will report back.')
            ->call('submitReply')
            ->assertSee('Reply saved, but the email failed:');

        $this->assertSame(2, $ticket->replies()->count());
    }

    /**
     * An SMTP server that refuses the connection, on the sync queue, so the
     * mailable is sent (and fails) inside the request, as on a fresh app.
     */
    private function breakTheMailer(): void
    {
        config([
            'queue.default' => 'sync',
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 2],
        ]);
    }

    private function openTicket(): Testable
    {
        return Livewire::actingAs($this->owner)
            ->test(CreateTicket::class)
            ->set('subject', 'Cannot download my invoice')
            ->set('category', 'billing')
            ->set('message', 'The invoice link on the billing page returns an error.')
            ->call('submit');
    }

    private function ticket(): SupportTicket
    {
        $ticket = SupportTicket::create([
            'user_id' => $this->owner->id,
            'subject' => 'Export is broken',
            'category' => 'bug',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $this->owner->id, 'message' => 'Nothing downloads.', 'is_admin_reply' => false]);

        return $ticket;
    }
}
