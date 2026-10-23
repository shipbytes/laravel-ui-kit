<?php

namespace Shipbytes\UiKit\Tests\Feature\Profile;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class AvatarTest extends InstalledKitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_an_upload_gets_a_random_name_and_replaces_the_last_one(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $form = Livewire::test('profile.update-profile-information-form')
            ->set('avatar', UploadedFile::fake()->image('me.png', 64, 64))
            ->call('updateAvatar')
            ->assertHasNoErrors();

        $first = $user->fresh()->avatar_path;
        $this->assertMatchesRegularExpression('#^avatars/[A-Za-z0-9]{40}\.png$#', $first, 'the key would make every photo on the public disk guessable');
        $this->assertNotSame('avatars/'.$user->id.'.png', $first);

        $form->set('avatar', UploadedFile::fake()->image('again.png', 64, 64))->call('updateAvatar');

        $second = $user->fresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_the_shells_show_the_photo(): void
    {
        $user = $this->makeAdmin();
        $user->forceFill(['avatar_path' => 'avatars/'.str_repeat('a', 40).'.png'])->save();
        $url = Storage::disk('public')->url($user->avatar_path);

        $this->assertSame($url, $user->fresh()->avatarUrl());

        // The admin rail's footer, and the user shell around the profile page.
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee($url, false);
        $this->get('/profile')->assertOk()->assertSee($url, false);
    }

    public function test_deleting_the_account_deletes_the_photo(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        Livewire::test('profile.update-profile-information-form')
            ->set('avatar', UploadedFile::fake()->image('me.png', 64, 64))
            ->call('updateAvatar');

        $path = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($path);

        Livewire::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        Storage::disk('public')->assertMissing($path);
    }
}
