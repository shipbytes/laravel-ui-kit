<?php

namespace Shipbytes\UiKit\Console\Concerns;

use Illuminate\Filesystem\Filesystem;
use Laravel\Prompts\Prompt;

trait InstallsModule
{
    protected function stubsPath(string $relative = ''): string
    {
        $base = realpath(__DIR__.'/../../../stubs');

        return $relative === '' ? $base : $base.DIRECTORY_SEPARATOR.ltrim($relative, '/\\');
    }

    /**
     * Force Laravel Prompts into Symfony-Console fallback mode on terminals
     * where its alt-screen rendering doesn't reliably work (Windows cmd,
     * WSL with some emulators). Overridable via UI_KIT_PROMPTS_FALLBACK=0|1.
     */
    protected function ensurePromptsRender(): void
    {
        $override = getenv('UI_KIT_PROMPTS_FALLBACK');

        if ($override === '0' || $override === 'false') {
            Prompt::fallbackWhen(false);

            return;
        }

        if ($override === '1' || $override === 'true') {
            Prompt::fallbackWhen(true);

            return;
        }

        $shouldFallback = PHP_OS_FAMILY === 'Windows'
            || getenv('WSL_DISTRO_NAME') !== false
            || getenv('WSL_INTEROP') !== false
            || ! stream_isatty(STDIN);

        Prompt::fallbackWhen($shouldFallback);
    }

    /**
     * Recursively copy a module directory layout into the host app.
     *
     * Any directory under the module stub that matches a known target key
     * is mapped into the app's canonical location.
     */
    protected function copyModuleTree(string $moduleSlug): void
    {
        $fs = new Filesystem();
        $source = $this->stubsPath("modules/{$moduleSlug}");

        if (! is_dir($source)) {
            $this->warn("No stubs directory for module '{$moduleSlug}' at {$source}. Nothing copied.");

            return;
        }

        $mappings = [
            'views' => resource_path('views'),
            'Livewire' => app_path('Livewire'),
            'Models' => app_path('Models'),
            'Http' => app_path('Http'),
            'migrations' => database_path('migrations'),
            'database' => database_path(),
            'config' => config_path(),
            'routes' => base_path('routes'),
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
            $this->line("  ✓ copied <info>{$stubDir}/</info>  →  ".str_replace(base_path().'/', '', $targetDir).'/');
        }
    }

    /**
     * Mark a module (or a module:provider pair) as installed in config/ui-kit.php.
     */
    protected function markInstalled(string $slug, ?string $provider = null): void
    {
        $configPath = config_path('ui-kit.php');

        if (! file_exists($configPath)) {
            $this->warn('config/ui-kit.php not found; cannot record installed module. Publish the config first.');

            return;
        }

        $config = require $configPath;
        $installed = $config['installed_modules'] ?? [];

        if ($provider) {
            $installed[$slug] = array_values(array_unique(array_merge($installed[$slug] ?? [], [$provider])));
        } else {
            $installed[$slug] = $installed[$slug] ?? [];
        }

        $config['installed_modules'] = $installed;

        file_put_contents($configPath, "<?php\n\nreturn ".$this->varExport($config).";\n");
    }

    protected function varExport(mixed $value, int $indent = 0): string
    {
        if (is_array($value)) {
            $isList = array_keys($value) === range(0, count($value) - 1);
            $pad = str_repeat('    ', $indent + 1);
            $closePad = str_repeat('    ', $indent);
            $lines = [];

            foreach ($value as $k => $v) {
                $keyPart = $isList ? '' : var_export($k, true).' => ';
                $lines[] = $pad.$keyPart.$this->varExport($v, $indent + 1).',';
            }

            if (empty($lines)) {
                return '[]';
            }

            return "[\n".implode("\n", $lines)."\n".$closePad.']';
        }

        return var_export($value, true);
    }
}
