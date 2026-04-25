<?php

namespace Shipbytes\UiKit\Console;

use Shipbytes\UiKit\Console\Concerns\InstallsModule;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    use InstallsModule;

    protected $signature = 'ui-kit:install
                            {--force : Overwrite existing files without prompting}
                            {--modules= : Comma-separated module slugs to install non-interactively}';

    protected $description = 'Install the UI Kit core and optionally pick modules interactively.';

    public function handle(ModuleRegistry $registry): int
    {
        $this->ensurePromptsRender();
        $this->resetDeferred();

        info('UI Kit — core install');

        if (($abortCode = $this->preflightAuthConflicts()) !== null) {
            return $abortCode;
        }

        note("The following core pieces will be installed:\n  • Auth pages (login, register, verify, etc.)\n  • Admin shell (sidebar + mobile nav)\n  • Dashboard stub + Users CRUD");

        $this->publishCore();
        $this->configureFortify();

        // The kit ships at least one core migration (add_is_admin_to_users_table).
        $this->deferMigrate();

        $selected = $this->resolveSelectedModules($registry);

        foreach ($selected as $slug) {
            $args = [
                'module' => $slug,
                '--from-parent' => true,
            ];

            // Pass through provider selection for analytics so non-interactive
            // installs work without a second prompt.
            if ($slug === 'analytics' && $this->option('modules') !== null) {
                // Default to all providers when invoking non-interactively with
                // --modules=all etc. Users can still pin specific ones via
                // --providers when running ui-kit:install-module directly.
                $args['--providers'] = 'utm,ga4,posthog';
            }

            $this->call('ui-kit:install-module', $args);
        }

        $this->newLine();
        $this->line('<comment>Running tail commands…</comment>');
        $this->runDeferredCommands();
        $this->generateUiKitUserTrait();

        $this->printFinalSummary($registry, $selected);

        return self::SUCCESS;
    }

    /**
     * Print a single consolidated checklist of the irreducibly-manual steps.
     *
     * @param  array<int, string>  $selected
     */
    protected function printFinalSummary(ModuleRegistry $registry, array $selected): void
    {
        $this->newLine();
        $this->line('<fg=green>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $this->line('<fg=green>UI Kit installed.</> A few small things still need your hand:');
        $this->line('<fg=green>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $this->newLine();

        $needsUserTrait = in_array('admin-middleware', $selected, true)
            || in_array('impersonation', $selected, true);

        $step = 1;

        if ($needsUserTrait) {
            $this->line("  <fg=yellow>{$step}.</> Add the kit's User trait to <info>app/Models/User.php</info>:");
            $this->line('       <fg=gray>use App\\Models\\Concerns\\UiKitUser;</>');
            $this->line('       <fg=gray>class User extends Authenticatable {</>');
            $this->line('       <fg=gray>    use UiKitUser;  // <-- add</>');
            $this->line('       <fg=gray>}</>');
            $this->newLine();
            $step++;
        }

        $this->line("  <fg=yellow>{$step}.</> Add the kit's component tags to your master layout (<info>resources/views/layouts/app.blade.php</info>):");
        $this->line('       <fg=gray>&lt;head&gt;</>');
        $this->line('       <fg=gray>    &lt;x-ui-kit::head /&gt;       <!-- analytics + dark-mode no-flash --&gt;</>');
        $this->line('       <fg=gray>&lt;/head&gt;</>');
        $this->line('       <fg=gray>&lt;body&gt;</>');
        $this->line('       <fg=gray>    &lt;x-ui-kit::banners /&gt;    <!-- impersonation ribbon --&gt;</>');
        $this->line('       <fg=gray>&lt;/body&gt;</>');
        $this->newLine();
        $step++;

        $this->line("  <fg=yellow>{$step}.</> Set <info>.env</info> keys for the features you enabled:");
        $this->line('       <fg=gray>MAIL_*</>           required for password reset / verification');
        if (in_array('analytics', $selected, true)) {
            $this->line('       <fg=gray>GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX</>   (analytics:ga4)');
            $this->line('       <fg=gray>POSTHOG_PUBLIC_KEY=phc_…</>            (analytics:posthog)');
        }
        $this->newLine();
        $step++;

        $this->line("  <fg=yellow>{$step}.</> Build assets:");
        $this->line('       <fg=gray>npm install &amp;&amp; npm run dev</>');
        $this->newLine();
        $step++;

        if (in_array('admin-middleware', $selected, true)) {
            $this->line("  <fg=yellow>{$step}.</> Make a user admin:");
            $this->line('       <fg=gray>php artisan tinker --execute="App\\\\Models\\\\User::find(1)-&gt;assignRole(\'admin\');"</>');
            $this->newLine();
            $step++;
        }

        // Show any module-specific residual notes.
        $residual = [];
        foreach ($selected as $slug) {
            $meta = $registry->get($slug);
            foreach ((array) ($meta['post_install_notes'] ?? []) as $note) {
                $residual[] = "<fg=gray>[{$slug}]</> {$note}";
            }
            foreach ((array) ($meta['providers_meta'] ?? []) as $provider => $pMeta) {
                foreach ((array) ($pMeta['post_install_notes'] ?? []) as $note) {
                    $residual[] = "<fg=gray>[{$slug}:{$provider}]</> {$note}";
                }
            }
        }

        if (! empty($residual)) {
            $this->line('  <fg=cyan>Optional / module-specific:</>');
            foreach ($residual as $note) {
                $this->line('       • '.$note);
            }
            $this->newLine();
        }

        $this->line('<fg=gray>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
    }

    protected function publishCore(): void
    {
        $tags = ['ui-kit-config', 'ui-kit-views', 'ui-kit-livewire', 'ui-kit-assets', 'ui-kit-routes', 'ui-kit-migrations'];

        foreach ($tags as $tag) {
            Artisan::call('vendor:publish', [
                '--tag' => $tag,
                '--force' => (bool) $this->option('force'),
            ]);
            $this->line("  ✓ published <info>{$tag}</info>");
        }
    }

    /**
     * Publish Fortify's config and disable its default view routes.
     *
     * The kit ships its own Volt-based login/register/etc. routes (in
     * routes/auth.php, auto-loaded by the service provider). If Fortify
     * also registers its default view routes, they collide on the same
     * paths and crash with "RegisterViewResponse is not instantiable"
     * because Fortify's view responses aren't bound without a published
     * FortifyServiceProvider.
     *
     * Flipping `views` to false tells Fortify to skip GET route
     * registration, leaving the kit's routes unopposed. Fortify still
     * registers its POST action routes (login/register/etc.) which the
     * kit doesn't use directly either (Livewire handles the form POSTs),
     * but those routes are harmless — they just sit there unused.
     */
    protected function configureFortify(): void
    {
        $path = config_path('fortify.php');

        if (! file_exists($path)) {
            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\\Fortify\\FortifyServiceProvider',
                '--tag' => 'fortify-config',
            ]);
            $this->line('  ✓ published <info>fortify-config</info>');
        }

        if (! file_exists($path)) {
            warning('Could not publish config/fortify.php. Set `views => false` there manually to avoid a /register 500.');

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, "'views' => true")) {
            file_put_contents($path, str_replace("'views' => true", "'views' => false", $contents));
            $this->line('  ✓ patched <info>fortify.php</info> <comment>views=false</comment> (kit supplies its own auth routes)');

            return;
        }

        if (str_contains($contents, "'views' => false")) {
            $this->line('  ✓ <info>fortify.php</info> already has views=false');

            return;
        }

        warning("Couldn't find a `views` flag in config/fortify.php. Set it to false manually so Fortify doesn't collide with the kit's auth routes.");
    }

    /**
     * Detect auth scaffolding that would collide with this kit.
     *
     * Returns null to proceed, or a command exit code to abort with.
     */
    protected function preflightAuthConflicts(): ?int
    {
        $hasJetstream = $this->composerHas('laravel/jetstream');
        $hasBreeze = $this->composerHas('laravel/breeze');

        $fileCollisions = array_values(array_filter([
            'routes/auth.php',
            'app/Livewire/Forms/LoginForm.php',
            'resources/views/livewire/pages/auth/login.blade.php',
        ], fn (string $rel) => file_exists(base_path($rel))));

        if (! $hasJetstream && ! $hasBreeze && empty($fileCollisions)) {
            return null;
        }

        if ($hasJetstream) {
            error('Jetstream detected (laravel/jetstream is installed).');
            note("This kit and Jetstream cannot coexist — they both register /login, /register, etc., and Jetstream adds teams/API-tokens this kit doesn't understand.\n\nRecommended path: start from a fresh Laravel app, or fully remove Jetstream first.\nSee README → \"Before you install — fresh vs. existing app\".");

            if (! $this->option('force')) {
                return self::FAILURE;
            }

            warning('Proceeding because --force was passed. Expect route collisions at boot.');
        }

        if ($hasBreeze) {
            warning('Breeze detected (laravel/breeze is installed).');
            note("Breeze and this kit overlap on routes/auth.php, app/Livewire/Forms/LoginForm.php, and resources/views/livewire/pages/auth/*. Without --force, Laravel will silently skip those files and you'll end up with a mixed UI.\n\nRecommended: remove Breeze first (see README).");
        }

        if (! empty($fileCollisions)) {
            warning("Existing files will be affected:\n  • ".implode("\n  • ", $fileCollisions)."\n\nWithout --force they'll be skipped. With --force they'll be overwritten.");
        }

        if ($this->option('force')) {
            warning('--force is set: existing files WILL be overwritten.');

            return null;
        }

        if (! $this->input->isInteractive()) {
            error('Auth scaffolding detected and running non-interactively. Re-run with --force to override, or remove the conflicting setup first. Aborting.');

            return self::FAILURE;
        }

        if (! confirm(label: 'Continue anyway?', default: false, hint: 'Colliding files will be left untouched; the kit will only drop in the non-overlapping pieces.')) {
            info('Aborted. See README → "Before you install" for safe migration steps.');

            return self::FAILURE;
        }

        return null;
    }

    protected function composerHas(string $package): bool
    {
        if (! class_exists(InstalledVersions::class)) {
            return false;
        }

        try {
            return InstalledVersions::isInstalled($package);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveSelectedModules(ModuleRegistry $registry): array
    {
        if ($this->option('modules') !== null) {
            $requested = array_filter(array_map('trim', explode(',', (string) $this->option('modules'))));
            $unknown = array_diff($requested, array_keys($registry->all()));

            if (! empty($unknown)) {
                $this->error('Unknown modules: '.implode(', ', $unknown));

                return [];
            }

            return array_values($requested);
        }

        if (! $this->input->isInteractive()) {
            return [];
        }

        return $this->pickModulesManually($registry);
    }

    /**
     * Render the module list with plain $this->line() + read from STDIN.
     *
     * We deliberately bypass Laravel Prompts' multiselect here because its
     * alt-screen rendering is unreliable on Windows cmd / Laragon / some WSL
     * emulators (renders invisibly, silently eats keystrokes). A manual
     * numbered list + comma-separated input works on every terminal.
     *
     * @return array<int, string>
     */
    protected function pickModulesManually(ModuleRegistry $registry): array
    {
        $slugs = array_keys($registry->all());

        $this->line('');
        $this->line('<fg=cyan>Optional modules:</>');
        foreach ($slugs as $i => $slug) {
            $meta = $registry->get($slug);
            $this->line(sprintf('  <fg=yellow>%2d</>. <info>%s</info> — %s', $i + 1, $slug, $meta['summary']));
        }
        $this->line('');
        $this->line('Type comma-separated numbers to install (e.g. <info>1,3,5</info>), <info>all</info> for everything, or press <info>Enter</info> to install core only.');
        $this->output->write('> ');

        $line = fgets(STDIN);

        if ($line === false) {
            return [];
        }

        $input = trim($line);

        if ($input === '') {
            return [];
        }

        if (strtolower($input) === 'all') {
            return $slugs;
        }

        $selected = [];
        foreach (array_filter(array_map('trim', explode(',', $input))) as $pick) {
            if (! ctype_digit($pick)) {
                $this->warn("Ignoring invalid entry: {$pick}");

                continue;
            }

            $idx = (int) $pick - 1;

            if (! isset($slugs[$idx])) {
                $this->warn("Ignoring out-of-range number: {$pick}");

                continue;
            }

            if (! in_array($slugs[$idx], $selected, true)) {
                $selected[] = $slugs[$idx];
            }
        }

        return $selected;
    }
}
