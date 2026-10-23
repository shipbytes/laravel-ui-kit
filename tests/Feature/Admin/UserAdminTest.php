<?php

namespace Shipbytes\UiKit\Tests\Feature\Admin;

use App\Livewire\Admin\Users\UserDetail;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class UserAdminTest extends InstalledKitTestCase
{
    public function test_admins_carry_an_admin_badge_in_the_list_and_detail(): void
    {
        $admin = $this->makeAdmin(['name' => 'Ada Admin']);
        $member = $this->makeUser(['name' => 'Mo Member']);

        $list = $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->assertRendersCleanly($list);
        $this->assertSame(1, $this->adminBadges($list->getContent()), 'one admin, one badge');

        $this->assertSame(1, $this->adminBadges($this->get("/admin/users/{$admin->id}")->assertOk()->getContent()));

        $member = $this->get("/admin/users/{$member->id}")->assertOk();
        $this->assertRendersCleanly($member);
        $this->assertSame(0, $this->adminBadges($member->getContent()));
    }

    /** ds::badge tone="accent" reading "Admin"; the rail's own "Admin" title is not one. */
    private function adminBadges(string|false $html): int
    {
        return preg_match_all('#<span class="[^"]*bg-accent-tint[^"]*">(?:\s|<!--.*?-->)*Admin\s*</span>#s', (string) $html);
    }

    public function test_a_member_can_be_deleted(): void
    {
        Storage::fake('public');
        $member = $this->makeUser();
        $member->forceFill(['avatar_path' => 'avatars/photo.png'])->save();
        Storage::disk('public')->put('avatars/photo.png', 'x');

        $this->actingAs($this->makeAdmin())
            ->get("/admin/users/{$member->id}")
            ->assertSee('Delete user')
            ->assertSee('wire:confirm', false);

        Livewire::test(UserDetail::class, ['user' => $member])
            ->call('deleteUser')
            ->assertRedirect('/admin/users');

        $this->assertNull(User::find($member->id));
        Storage::disk('public')->assertMissing('avatars/photo.png');
    }

    public function test_another_admin_cannot_be_deleted(): void
    {
        $other = $this->makeAdmin();
        $this->actingAs($this->makeAdmin());

        $this->get("/admin/users/{$other->id}")->assertOk()->assertDontSee('Delete user');

        // The button is gone, but an action can be called without it.
        Livewire::test(UserDetail::class, ['user' => $other])
            ->call('deleteUser')
            ->assertForbidden();

        $this->assertNotNull(User::find($other->id));
    }

    public function test_you_cannot_delete_yourself(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->get("/admin/users/{$admin->id}")->assertOk()->assertDontSee('Delete user');

        Livewire::test(UserDetail::class, ['user' => $admin])
            ->call('deleteUser')
            ->assertForbidden();

        $this->assertNotNull(User::find($admin->id));
    }
}
