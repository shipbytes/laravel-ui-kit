<div class="max-w-3xl">
    <div class="mb-8">
        <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">Profile</h1>
        <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">Manage your account settings and preferences.</p>
    </div>

    <div class="space-y-6">
        <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-zinc-950/5 dark:ring-white/10">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-zinc-950/5 dark:ring-white/10">
            <livewire:profile.update-password-form />
        </div>

        @if(class_exists(\Laravel\Fortify\Actions\EnableTwoFactorAuthentication::class))
        <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-zinc-950/5 dark:ring-white/10">
            <livewire:profile.two-factor-authentication-form />
        </div>
        @endif

        <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 rounded-lg shadow-sm ring-1 ring-zinc-950/5 dark:ring-white/10">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</div>
