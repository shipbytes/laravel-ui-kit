<?php

namespace Shipbytes\UiKit\Console;

use Shipbytes\UiKit\Console\Concerns\InstallsModule;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

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

        $this->info("Module <comment>{$slug}</comment> installed.");

        if (! empty($meta['post_install_notes'])) {
            $this->newLine();
            $this->line('<comment>Next steps:</comment>');
            foreach ($meta['post_install_notes'] as $i => $note) {
                $this->line('  '.($i + 1).'. '.$note);
            }
        }

        return self::SUCCESS;
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

        return multiselect(
            label: "Which {$meta['label']} providers?",
            options: array_combine($meta['providers'], $meta['providers']),
            required: true
        );
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
