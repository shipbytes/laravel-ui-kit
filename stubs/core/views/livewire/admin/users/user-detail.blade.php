<div>
    <a href="{{ route(config('admin.route_name_prefix').'users.index') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-800 mb-4 inline-flex items-center gap-1">← All users</a>

    <div class="flex items-center gap-4 mb-6">
        <div class="size-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xl font-semibold">
            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-boldtext text-zinc-950 dark:text-zinc-50">{{ $user->name }}</h1>
            <p class="text-sm text-zinc-500">{{ $user->email }}</p>
        </div>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-xl ring-1 ring-zinc-950/5 p-4 dark:ring-white/5">
            <dt class="text-xs uppercase text-zinc-500">Joined</dt>
            <dd class="mt-1 text-sm text-zinc-950 dark:text-zinc-100">{{ $user->created_at->format('F j, Y') }}</dd>
        </div>
        <div class="rounded-xl ring-1 ring-zinc-950/5 p-4 dark:ring-white/5">
            <dt class="text-xs uppercase text-zinc-500">Email verified</dt>
            <dd class="mt-1 text-sm text-zinc-950 dark:text-zinc-100">{{ $user->email_verified_at?->format('F j, Y') ?? 'Not verified' }}</dd>
        </div>
    </dl>
</div>
