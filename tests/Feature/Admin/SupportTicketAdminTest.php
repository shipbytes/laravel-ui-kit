<?php

namespace Shipbytes\UiKit\Tests\Feature\Admin;

use App\Livewire\Admin\Support\TicketDetail;
use App\Models\SupportTicket;
use App\Models\User;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class SupportTicketAdminTest extends InstalledKitTestCase
{
    protected string $modules = 'support-tickets';

    private User $admin;

    private SupportTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeAdmin();
        $this->ticket = SupportTicket::create([
            'user_id' => $this->makeUser()->id,
            'subject' => 'Cannot export',
            'category' => 'bug',
            'priority' => 'low',
            'status' => 'open',
        ]);
    }

    public function test_an_admin_changes_the_status(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TicketDetail::class, ['ulid' => $this->ticket->ulid])
            ->set('newStatus', 'resolved')
            ->call('updateStatus')
            ->assertHasNoErrors();

        $this->assertSame('resolved', $this->ticket->fresh()->status);
        $this->assertNotNull($this->ticket->fresh()->closed_at, 'resolving closes the ticket');

        Livewire::actingAs($this->admin)
            ->test(TicketDetail::class, ['ulid' => $this->ticket->ulid])
            ->set('newStatus', 'nonsense')
            ->call('updateStatus')
            ->assertHasErrors(['newStatus']);
    }

    public function test_an_admin_changes_the_priority(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TicketDetail::class, ['ulid' => $this->ticket->ulid])
            ->set('newPriority', 'high')
            ->call('updatePriority')
            ->assertHasNoErrors();

        $this->assertSame('high', $this->ticket->fresh()->priority);
    }

    public function test_an_admin_reply_is_flagged_as_one(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TicketDetail::class, ['ulid' => $this->ticket->ulid])
            ->set('reply', 'We have found the problem and are fixing it.')
            ->call('submitReply')
            ->assertHasNoErrors()
            ->assertSet('reply', '');

        $reply = $this->ticket->replies()->sole();
        $this->assertTrue($reply->is_admin_reply);
        $this->assertTrue($reply->user->is($this->admin));
    }

    public function test_a_member_is_refused(): void
    {
        $this->actingAs($this->makeUser())->get('/admin/support')->assertForbidden();
        $this->get("/admin/support/{$this->ticket->ulid}")->assertForbidden();
    }
}
