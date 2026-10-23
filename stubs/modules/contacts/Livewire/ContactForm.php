<?php

namespace App\Livewire;

use App\Mail\ContactConfirmationMail;
use App\Mail\ContactSubmittedMail;
use App\Models\ContactSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Livewire\Concerns\WithSpamProtection;
use Shipbytes\UiKit\Support\UiKit;

/**
 * The public contact form. Each message lands in the admin inbox
 * (/admin/contacts) and is mailed to the admins, and the sender gets a copy.
 * The subjects are `contacts.subjects` in config/ui-kit.php.
 */
#[Layout('layouts.user-shell')]
class ContactForm extends Component
{
    use WithSpamProtection;

    public string $name = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    public bool $submitted = false;

    public function mount(): void
    {
        // A signed-in visitor should not have to type who they are.
        $this->name = (string) (Auth::user()->name ?? '');
        $this->email = (string) (Auth::user()->email ?? '');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', Rule::in($this->subjects())],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function submit(): void
    {
        // A bot sees the same thank-you as a person, and nothing is saved.
        if ($this->caughtInHoneypot()) {
            $this->reset(['subject', 'message', 'website']);
            $this->submitted = true;

            return;
        }

        $this->validate();

        if (! $this->passesRateLimit('contact-form:'.request()->ip(), 3)) {
            return;
        }

        $submission = ContactSubmission::create([
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'ip_address' => request()->ip(),
        ]);

        // Saved first: a mail that fails is logged and never loses the message.
        UiKit::sendMail(UiKit::admins(), new ContactSubmittedMail($submission));
        UiKit::sendMail($submission->email, new ContactConfirmationMail($submission));

        $this->reset(['subject', 'message']);
        $this->submitted = true;
    }

    public function sendAnother(): void
    {
        $this->submitted = false;
        $this->resetValidation();
    }

    /**
     * @return array<int, string>
     */
    protected function subjects(): array
    {
        return array_values((array) config('ui-kit.contacts.subjects', []));
    }

    public function render()
    {
        return view('livewire.contact-form', [
            'subjects' => $this->subjects(),
        ]);
    }
}
