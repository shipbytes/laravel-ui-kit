<?php

namespace Shipbytes\UiKit\Console;

use Shipbytes\UiKit\Console\Concerns\InstallsModule;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;

class InstallModuleCommand extends Command
{
    use InstallsModule;

    protected $signature = 'ui-kit:install-module
                            {module : The module slug (e.g. support-tickets, analytics)}
                            {--providers= : Comma-separated sub-providers (for the analytics module)}
                            {--force : Overwrite existing files without prompting}
                            {--from-parent : Internal flag set by ui-kit:install to suppress duplicate notices}';

    protected $description = 'Install a single UI Kit module by slug.';

    public function handle(ModuleRegistry $registry): int
    {
        $this->ensurePromptsRender();

        $slug = (string) $this->argument('module');

        if (! $registry->has($slug)) {
            $this->error("Unknown module: {$slug}");
            $this->line('Available: '.implode(', ', array_keys($registry->all())));

            return self::FAILURE;
        }

        $meta = $registry->get($slug);
        $this->line("Installing <info>{$meta['label']}</info>...");

        if (! empty($meta['composer'])) {
            $this->installComposerDeps($meta['composer']);
        }

        $providers = $this->resolveProviders($slug, $meta);

        if ($providers === null) {
            return self::FAILURE;
        }

        $this->copyModuleTree($slug);

        if (empty($providers)) {
            $this->markInstalled($slug);
        } else {
            foreach ($providers as $provider) {
                $providerStub = $this->stubsPath("modules/{$slug}/providers/{$provider}");

                if (is_dir($providerStub)) {
                    $this->copyProviderTree($slug, $provider);
                }

                $this->markInstalled($slug, $provider);
                $this->line("  ✓ provider <info>{$provider}</info> enabled");
            }
        }

        // Apply auto-patches and defer artisan/npm commands declared in metadata.
        $this->applyModuleAutomation($slug, $meta, $providers);

        // When called standalone (not from the parent installer), drain deferred
        // commands now. The parent runs them after all modules finish.
        if (! $this->option('from-parent')) {
            $this->newLine();
            $this->line('<comment>Running tail commands…</comment>');
            $this->runDeferredCommands();

            // Regenerate UiKitUser trait based on the now-current installed set.
            // (Parent installer regenerates once at the end of all modules.)
            if (in_array($slug, ['admin-middleware', 'impersonation'], true)) {
                $this->generateUiKitUserTrait();
            }
        }

        $this->info("Module <comment>{$slug}</comment> installed.");

        // Suppress per-module manual notes when invoked by the parent installer
        // — InstallCommand prints one consolidated final summary covering all
        // selected modules. When run standalone, surface them here.
        if (! $this->option('from-parent')) {
            $manualNotes = $this->collectManualNotes($meta, $providers);

            if (! empty($manualNotes)) {
                $this->newLine();
                $this->line('<comment>Manual steps still needed:</comment>');
                foreach ($manualNotes as $i => $note) {
                    $this->line('  '.($i + 1).'. '.$note);
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Read the module's structured metadata and apply patches / queue commands.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<int, string>  $providers
     */
    protected function applyModuleAutomation(string $slug, array $meta, array $providers): void
    {
        // Module-level fields (apply unconditionally)
        if (! empty($meta['admin_middleware_swap'])) {
            $this->patchAdminMiddleware();
        }

        if (! empty($meta['admin_routes'])) {
            $this->patchAdminRoutes($meta['admin_routes']);
        }

        if (! empty($meta['admin_nav'])) {
            $this->patchAdminNav($meta['admin_nav']);
        }

        if (! empty($meta['user_routes'])) {
            $this->patchUserRoutes($meta['user_routes']);
        }

        if (! empty($meta['artisan_publish'])) {
            foreach ($meta['artisan_publish'] as $args) {
                $this->deferVendorPublish($args);
            }
        }

        if (! empty($meta['artisan_seed'])) {
            foreach ((array) $meta['artisan_seed'] as $class) {
                $this->deferSeeder($class);
            }
        }

        if (! empty($meta['storage_link'])) {
            $this->deferStorageLink();
        }

        if (! empty($meta['npm'])) {
            $this->deferNpmInstall((array) $meta['npm']);
        }

        // Anything that copies a migration also implies "we need to migrate".
        // Cheap heuristic: if the module's stub dir contains a migrations folder,
        // schedule a migrate. Same for any artisan_publish that exists.
        $stubMigrations = $this->stubsPath("modules/{$slug}/migrations");
        if (is_dir($stubMigrations) || ! empty($meta['artisan_publish'])) {
            $this->deferMigrate();
        }

        // Per-provider metadata (only for picked providers).
        $perProvider = $meta['providers_meta'] ?? [];
        foreach ($providers as $provider) {
            $pMeta = $perProvider[$provider] ?? [];

            if (! empty($pMeta['admin_routes'])) {
                $this->patchAdminRoutes($pMeta['admin_routes']);
            }
            if (! empty($pMeta['admin_nav'])) {
                $this->patchAdminNav($pMeta['admin_nav']);
            }
            if (! empty($pMeta['user_routes'])) {
                $this->patchUserRoutes($pMeta['user_routes']);
            }
            if (! empty($pMeta['npm'])) {
                $this->deferNpmInstall((array) $pMeta['npm']);
            }
            if (! empty($pMeta['artisan_publish'])) {
                foreach ($pMeta['artisan_publish'] as $args) {
                    $this->deferVendorPublish($args);
                }
            }

            // Provider stubs may also include migrations (e.g. analytics:utm).
            $providerMigrations = $this->stubsPath("modules/{$slug}/providers/{$provider}/migrations");
            if (is_dir($providerMigrations)) {
                $this->deferMigrate();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<int, string>  $providers
     * @return array<int, string>
     */
    protected function collectManualNotes(array $meta, array $providers): array
    {
        $notes = (array) ($meta['post_install_notes'] ?? []);

        $perProvider = $meta['providers_meta'] ?? [];
        foreach ($providers as $provider) {
            $pNotes = (array) ($perProvider[$provider]['post_install_notes'] ?? []);
            $notes = array_merge($notes, $pNotes);
        }

        return array_values(array_unique($notes));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<int, string>|null  null signals an error
     */
    protected function resolveProviders(string $slug, array $meta): ?array
    {
        if (empty($meta['providers'])) {
            return [];
        }

        if ($this->option('providers') !== null) {
            $requested = array_filter(array_map('trim', explode(',', (string) $this->option('providers'))));
            $unknown = array_diff($requested, $meta['providers']);

            if (! empty($unknown)) {
                $this->error("Unknown {$slug} providers: ".implode(', ', $unknown));

                return null;
            }

            return array_values($requested);
        }

        if (! $this->input->isInteractive()) {
            return $meta['providers'];
        }

        return $this->pickProvidersManually($slug, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<int, string>
     */
    protected function pickProvidersManually(string $slug, array $meta): array
    {
        /** @var array<int, string> $providers */
        $providers = $meta['providers'];

        while (true) {
            $this->line('');
            $this->line("<fg=cyan>Available {$meta['label']} providers:</>");
            foreach ($providers as $i => $provider) {
                $this->line(sprintf('  <fg=yellow>%2d</>. <info>%s</info>', $i + 1, $provider));
            }
            $this->line('');
            $this->line('Type comma-separated numbers (e.g. <info>1,3</info>) or <info>all</info>:');
            $this->output->write('> ');

            $line = fgets(STDIN);
            $input = $line === false ? '' : trim($line);

            if ($input === '') {
                $this->warn('At least one provider is required.');

                continue;
            }

            if (strtolower($input) === 'all') {
                return $providers;
            }

            $selected = [];
            $invalid = false;

            foreach (array_filter(array_map('trim', explode(',', $input))) as $pick) {
                if (! ctype_digit($pick)) {
                    $this->warn("Invalid entry: {$pick}");
                    $invalid = true;
                    break;
                }

                $idx = (int) $pick - 1;

                if (! isset($providers[$idx])) {
                    $this->warn("Out of range: {$pick}");
                    $invalid = true;
                    break;
                }

                if (! in_array($providers[$idx], $selected, true)) {
                    $selected[] = $providers[$idx];
                }
            }

            if ($invalid || empty($selected)) {
                continue;
            }

            return $selected;
        }
    }

    protected function copyProviderTree(string $slug, string $provider): void
    {
        $fs = new \Illuminate\Filesystem\Filesystem();
        $source = $this->stubsPath("modules/{$slug}/providers/{$provider}");

        $mappings = [
            'views' => resource_path('views'),
            'Livewire' => app_path('Livewire'),
            'Models' => app_path('Models'),
            'Http' => app_path('Http'),
            'migrations' => database_path('migrations'),
            'database' => database_path(),
            'config' => config_path(),
            'js' => resource_path('js'),
            'css' => resource_path('css'),
        ];

        foreach ($mappings as $stubDir => $targetDir) {
            $from = $source.'/'.$stubDir;
            if (! is_dir($from)) {
                continue;
            }
            $fs->ensureDirectoryExists($targetDir);
            $fs->copyDirectory($from, $targetDir);
        }
    }

    /**
     * @param  array<int, string>  $packages
     */
    protected function installComposerDeps(array $packages): void
    {
        $missing = array_filter($packages, function (string $requirement) {
            [$name] = explode(':', $requirement, 2);

            return ! class_exists(\Composer\InstalledVersions::class)
                || ! \Composer\InstalledVersions::isInstalled($name);
        });

        if (empty($missing)) {
            return;
        }

        if (! confirm("This module requires: ".implode(', ', $missing).". Run composer require now?", default: true)) {
            $this->warn("Skipped composer require. You must install manually: composer require ".implode(' ', $missing));

            return;
        }

        $process = new Process(array_merge(['composer', 'require'], $missing), base_path());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }
}
