<div class="space-y-6">
    <x-ui-kit::page-header title="Dashboard" :subtitle="'Welcome back, '.(auth()->user()->name ?? '').'.'" />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ds::stat-tile label="Total users" :value="$stats['users']" caption="All accounts" />
        <x-ds::stat-tile label="Verified" :value="$stats['verified']" caption="Email confirmed" />
        <x-ds::stat-tile label="New this week" :value="$stats['recent']" caption="Joined in the last 7 days" />
    </div>
</div>
