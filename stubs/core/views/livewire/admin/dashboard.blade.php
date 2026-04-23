<div>
    <div class="mb-8">
        <h1 class="text-2xl font-boldtext text-zinc-950 dark:text-zinc-50">Dashboard</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Welcome back, {{ auth()->user()->name ?? '' }}.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 p-5">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total users</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($stats['users']) }}</div>
        </div>
        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 p-5">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Verified</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($stats['verified']) }}</div>
        </div>
        <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900 ring-1 ring-zinc-950/5 dark:ring-white/5 p-5">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">New this week</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($stats['recent']) }}</div>
        </div>
    </div>
</div>
