<div class="max-w-3xl">
    <div class="mb-8">
        <h1 class="text-title font-semibold text-fg">Profile</h1>
        <p class="mt-2 text-body text-fg-muted">Manage your account settings and preferences.</p>
    </div>

    <div class="space-y-6">
        <x-ds::panel body="plain">
            <livewire:profile.update-profile-information-form />
        </x-ds::panel>

        <x-ds::panel body="plain">
            <livewire:profile.update-password-form />
        </x-ds::panel>

        @if(\Laravel\Fortify\Features::canManageTwoFactorAuthentication() && method_exists(auth()->user(), 'hasEnabledTwoFactorAuthentication'))
        <x-ds::panel body="plain">
            <livewire:profile.two-factor-authentication-form />
        </x-ds::panel>
        @endif

        <x-ds::panel body="plain">
            <livewire:profile.delete-user-form />
        </x-ds::panel>
    </div>
</div>
