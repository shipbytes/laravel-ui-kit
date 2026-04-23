<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-sidebar')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'recent' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }
}
