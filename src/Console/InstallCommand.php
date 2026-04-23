<?php

namespace Shipbytes\UiKit\Console;

use Shipbytes\UiKit\Console\Concerns\InstallsModule;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;

class InstallCommand extends Command
{
    use InstallsModule;

    protected $signature = 'ui-kit:install
                            {--force : Overwrite existing files without prompting}
                            {--modules= : Comma-separated module slugs to install non-interactively}';

    protected $description = 'Install the UI Kit core and optionally pick modules interactively.';

    public function handle(ModuleRegistry $registry): int
    {
        info('UI Kit — core install');
        note("The following core pieces will be installed:\n  • Auth pages (login, register, verify, etc.)\n  • Admin shell (sidebar + mobile nav)\n  • Dashboard stub + Users CRUD");

        $this->publishCore();

        $selected = $this->resolveSelectedModules($registry);

        foreach ($selected as $slug) {
            $this->call('ui-kit:install-module', [
                'module' => $slug,
                '--from-parent' => true,
            ]);
        }

        note("Next steps:\n  1. php artisan migrate\n  2. npm install && npm run dev\n  3. Add `require('shipbytes/laravel-ui-kit/tailwind-preset')` to tailwind.config.js");

        return self::SUCCESS;
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

        $options = [];
        foreach ($registry->all() as $slug => $meta) {
            $options[$slug] = $meta['label'].' — '.$meta['summary'];
        }

        return multiselect(
            label: 'Select optional modules to install',
            options: $options,
            hint: 'Space to toggle, Enter to confirm. You can install more later with ui-kit:install-module.'
        );
    }
}
