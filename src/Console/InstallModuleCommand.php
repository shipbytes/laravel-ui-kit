<?php

namespace Shipbytes\UiKit\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Shipbytes\UiKit\Console\Concerns\InstallsModule;
use Shipbytes\UiKit\Support\InstallQueue;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;

class InstallModuleCommand extends Command
{
    use InstallsModule;

    protected $signature = 'ui-kit:install-module
                            {module : The module slug (e.g. support-tickets, changelog)}
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

        $this->copyModuleTree($slug);
        $this->markInstalled($slug);

        // Apply auto-patches and defer artisan commands declared in metadata.
        $this->applyModuleAutomation($slug, $meta);

        // When called standalone (not from the parent installer), drain deferred
        // commands now. The parent runs them after all modules finish.
        if (! $this->option('from-parent')) {
            $this->newLine();
            $this->line('<comment>Running tail commands…</comment>');
            $this->runDeferredCommands();

            // Regenerate UiKitUser trait based on the now-current installed set.
            // (Parent installer regenerates once at the end of all modules.)
            if (in_array($slug, ['admin-middleware', 'impersonation', 'profile'], true)) {
                $this->generateUiKitUserTrait();
            }
        }

        $this->info("Module <comment>{$slug}</comment> installed.");

        // Suppress per-module manual notes when invoked by the parent installer
        // — InstallCommand prints one consolidated final summary covering all
        // selected modules. When run standalone, surface them here.
        if (! $this->option('from-parent')) {
            $manualNotes = (array) ($meta['post_install_notes'] ?? []);

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
     */
    protected function applyModuleAutomation(string $slug, array $meta): void
    {
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

        // Anything that copies a migration also implies "we need to migrate".
        // Cheap heuristic: if the module's stub dir contains a migrations folder,
        // schedule a migrate. Same for any artisan_publish that exists.
        $stubMigrations = $this->stubsPath("modules/{$slug}/migrations");
        if (is_dir($stubMigrations) || ! empty($meta['artisan_publish'])) {
            $this->deferMigrate();
        }
    }

    /**
     * @param  array<int, string>  $packages
     */
    protected function installComposerDeps(array $packages): void
    {
        $missing = array_filter($packages, function (string $requirement) {
            [$name] = explode(':', $requirement, 2);

            return ! class_exists(InstalledVersions::class)
                || ! InstalledVersions::isInstalled($name);
        });

        if (empty($missing)) {
            return;
        }

        if (! confirm('This module requires: '.implode(', ', $missing).'. Run composer require now?', default: true)) {
            $this->warn('Skipped composer require. You must install manually: composer require '.implode(' ', $missing));

            return;
        }

        $composer = (new ExecutableFinder)->find('composer', 'composer');

        $process = new Process(array_merge([$composer, 'require', '--no-interaction'], $missing), base_path());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->warn('composer require exited non-zero. Re-run `composer require '.implode(' ', $missing).'` manually.');

            return;
        }

        // The running process can't see the new packages — flag the queue so
        // tail commands run in a fresh subprocess (see runArtisan()).
        InstallQueue::$composerChanged = true;
    }
}
