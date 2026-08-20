<?php

namespace App\Livewire\Profile;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.user-shell')]
class ProfilePage extends Component
{
    public function render()
    {
        return view('livewire.profile.profile-page');
    }
}
