<?php

namespace Shipbytes\UiKit\Tests\Feature\Contacts;

use App\Livewire\Admin\Contacts\ContactList;
use App\Livewire\ContactForm;
use App\Mail\ContactConfirmationMail;
use App\Mail\ContactReplyMail;
use App\Mail\ContactSubmittedMail;
use App\Models\ContactSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * The contacts module's public half, /contact, and the inbox reply it makes
 * possible: what is saved, what is refused, and every mail's recipients.
 */
class ContactFormTest extends InstalledKitTestCase
{
    protected string $modules = 'contacts';

    public function test_a_guest_can_open_the_form(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('Contact us')
            ->assertSeeInOrder(config('ui-kit.contacts.subjects'))
            ->assertSee('name="website"', false)
            ->assertSee('tabindex="-1"', false);
    }

    public function test_the_form_is_validated(): void
    {
        Livewire::test(ContactForm::class)
            ->call('submit')
            ->assertHasErrors(['name' => 'required', 'email' => 'required', 'subject' => 'required', 'message' => 'required'])
            ->set('name', str_repeat('a', 256))
            ->set('email', 'not-an-address')
            ->set('subject', 'Free money')
            ->set('message', 'Too short')
            ->call('submit')
            ->assertHasErrors(['name' => 'max', 'email' => 'email', 'subject' => 'in', 'message' => 'min'])
            ->set('message', str_repeat('a', 5001))
            ->call('submit')
            ->assertHasErrors(['message' => 'max']);

        $this->assertSame(0, ContactSubmission::count());
    }

    public function test_a_message_is_saved_and_mailed_both_ways(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin(['email' => 'ada@example.com']);
        $this->makeUser(['email' => 'member@example.com']);

        $this->send()
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSet('message', '')
            ->assertSee('Thanks, your message is on its way')
            ->assertSee('Send another');

        $submission = ContactSubmission::sole();
        $this->assertSame('Jane Doe', $submission->name);
        $this->assertSame('jane@example.com', $submission->email);
        $this->assertSame('General question', $submission->subject);
        $this->assertSame('127.0.0.1', $submission->ip_address);
        $this->assertSame('unread', $submission->fresh()->status);

        Mail::assertQueuedCount(2);
        Mail::assertQueued(ContactSubmittedMail::class, fn (ContactSubmittedMail $mail) => $mail->hasTo('ada@example.com')
            && $mail->hasReplyTo('jane@example.com', 'Jane Doe'));
        Mail::assertQueued(ContactConfirmationMail::class, fn (ContactConfirmationMail $mail) => $mail->hasTo('jane@example.com')
            && ! $mail->hasTo($admin->email));
    }

    public function test_send_another_brings_the_form_back(): void
    {
        Mail::fake();

        $this->send()
            ->assertSet('submitted', true)
            ->call('sendAnother')
            ->assertSet('submitted', false)
            ->assertSee('Send message')
            ->assertSet('email', 'jane@example.com');
    }

    public function test_a_signed_in_visitor_is_prefilled(): void
    {
        $user = $this->makeUser(['name' => 'Olive Owner', 'email' => 'olive@example.com']);

        Livewire::actingAs($user)
            ->test(ContactForm::class)
            ->assertSet('name', 'Olive Owner')
            ->assertSet('email', 'olive@example.com');
    }

    public function test_the_honeypot_fakes_success_and_saves_nothing(): void
    {
        Mail::fake();

        $this->send(['website' => 'https://spam.example'])
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee('Thanks, your message is on its way');

        $this->assertSame(0, ContactSubmission::count());
        Mail::assertNothingOutgoing();
    }

    public function test_three_messages_an_hour_from_one_address(): void
    {
        Mail::fake();

        // Failed validation never reaches the limiter.
        foreach (range(1, 4) as $i) {
            $this->send(['message' => 'short'])->assertHasErrors(['message']);
        }

        foreach (range(1, 3) as $i) {
            $this->send(['message' => "Message number {$i}, long enough."])->assertHasNoErrors();
        }

        $this->send()->assertHasErrors(['limit'])->assertSet('submitted', false)->assertSee("That's the limit for now");
        $this->assertSame(3, ContactSubmission::count());

        // The count is per address, so another visitor has the full three.
        $this->assertSame(3, RateLimiter::attempts('contact-form:127.0.0.1'));
        $this->assertSame(0, RateLimiter::attempts('contact-form:203.0.113.9'));
    }

    public function test_the_three_mailables(): void
    {
        $submission = ContactSubmission::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Account or billing',
            'message' => "Hello,\n\nWhere can I find last year's invoices?",
            'ip_address' => '127.0.0.1',
        ]);

        $submitted = new ContactSubmittedMail($submission);
        $submitted->assertHasSubject('[Contact] Account or billing, from Jane Doe');
        $submitted->assertHasReplyTo('jane@example.com');
        $submitted->assertSeeInHtml('jane@example.com');
        $submitted->assertSeeInHtml(route('admin.contacts.index'));
        $submitted->assertSeeInText("> Where can I find last year's invoices?");

        $confirmation = new ContactConfirmationMail($submission);
        $confirmation->assertHasSubject('We got your message: Account or billing');
        $confirmation->assertSeeInHtml('Hi Jane Doe');
        $confirmation->assertSeeInText("> Where can I find last year's invoices?");

        $submission->update(['reply' => 'They are under Billing, then History.', 'replied_at' => now(), 'status' => 'replied']);

        $reply = new ContactReplyMail($submission->fresh());
        $reply->assertHasSubject('Re: Account or billing');
        // The answer comes first, the message it answers after it, quoted.
        $reply->assertSeeInOrderInText(['They are under Billing, then History.', 'you wrote:', "> Where can I find last year's invoices?"]);
    }

    public function test_the_inbox_reply_is_sent(): void
    {
        Mail::fake();
        $submission = $this->submission();

        Livewire::actingAs($this->makeAdmin())
            ->test(ContactList::class)
            ->call('openModal', $submission->id)
            ->set('replyText', 'Thanks for asking, here is the answer.')
            ->call('submitReply', $submission->id)
            ->assertHasNoErrors()
            ->assertSee('Reply sent to jane@example.com.');

        $this->assertSame('replied', $submission->fresh()->status);
        Mail::assertQueuedCount(1);
        Mail::assertQueued(ContactReplyMail::class, fn (ContactReplyMail $mail) => $mail->hasTo('jane@example.com')
            && $mail->submission->reply === 'Thanks for asking, here is the answer.');
    }

    public function test_a_mail_that_cannot_be_sent_never_loses_the_message_or_the_reply(): void
    {
        config([
            'queue.default' => 'sync',
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 2],
        ]);
        Log::spy();
        $admin = $this->makeAdmin();

        $this->send()->assertHasNoErrors()->assertSet('submitted', true);
        $this->assertSame(1, ContactSubmission::count());
        Log::shouldHaveReceived('error')->withArgs(fn (string $message) => $message === 'ui-kit: could not send App\Mail\ContactConfirmationMail')->once();

        $submission = ContactSubmission::sole();

        Livewire::actingAs($admin)
            ->test(ContactList::class)
            ->set('replyText', 'Thanks for asking, here is the answer.')
            ->call('submitReply', $submission->id)
            ->assertSee('Reply saved, but the email failed:');

        $this->assertSame('Thanks for asking, here is the answer.', $submission->fresh()->reply);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function send(array $overrides = []): Testable
    {
        $form = Livewire::test(ContactForm::class);

        foreach ($overrides + [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'General question',
            'message' => 'Hello, I have a question about your service.',
        ] as $field => $value) {
            $form->set($field, $value);
        }

        return $form->call('submit');
    }

    private function submission(): ContactSubmission
    {
        return ContactSubmission::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'General question',
            'message' => 'Hello, I have a question about your service.',
        ]);
    }
}
