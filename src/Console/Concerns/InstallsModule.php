<?php

namespace Shipbytes\UiKit\Console\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Laravel\Prompts\Prompt;

trait InstallsModule
{
    /**
     * Deferred-command state is process-wide so a child command invoked via
     * $this->call('ui-kit:install-module', …) can register commands that the
     * parent ui-kit:install drains at the end of the run.
     */

    /** @var array<int, string> */
    protected static array $deferredVendorPublishes = [];

    /** @var array<int, string> */
    protected static array $deferredSeeders = [];

    /** @var bool */
    protected static bool $deferredStorageLink = false;

    /** @var bool */
    protected static bool $deferredMigrate = false;

    protected function resetDeferred(): void
    {
        static::$deferredVendorPublishes = [];
        static::$deferredSeeders = [];
        static::$deferredStorageLink = false;
        static::$deferredMigrate = false;
    }

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
     * Record a module as installed in config/ui-kit.php.
     *
     * Patches only the slug list between the ui-kit:modules markers so the
     * rest of the file — env() calls, comments, consumer edits — survives
     * untouched. Also updates the in-memory config so later steps in the
     * same process (trait generation, module listing) see the new state.
     */
    protected function markInstalled(string $slug): void
    {
        $configPath = config_path('ui-kit.php');

        if (! file_exists($configPath)) {
            $this->warn('config/ui-kit.php not found; cannot record installed module. Publish the config first.');

            return;
        }

        $contents = file_get_contents($configPath);
        $startMarker = '/* ui-kit:modules-start */';
        $endMarker = '/* ui-kit:modules-end */';

        if (! str_contains($contents, $startMarker) || ! str_contains($contents, $endMarker)) {
            $this->warn('config/ui-kit.php is missing the ui-kit:modules markers; cannot record installed module. Re-publish ui-kit-config (a config published by an older kit version needs the markers added by hand).');

            return;
        }

        $existing = $this->extractMarkerBlock($contents, $startMarker, $endMarker);

        if (! str_contains($existing, "'{$slug}'")) {
            $pad = str_repeat(' ', 8);
            $existingBody = rtrim(ltrim($existing, "\n"));
            $injected = ($existingBody !== '' ? $existingBody."\n" : '')
                .$pad."'{$slug}',";

            $replacement = $startMarker."\n".$injected."\n".$pad.$endMarker;
            $pattern = '/'.preg_quote($startMarker, '/').'.*?'.preg_quote($endMarker, '/').'/s';
            file_put_contents($configPath, preg_replace($pattern, $replacement, $contents));
        }

        // Keep the running process in sync — the file alone isn't enough
        // because config was loaded at boot.
        $installed = config('ui-kit.installed_modules', []);
        if (! in_array($slug, $installed, true)) {
            $installed[] = $slug;
            config(['ui-kit.installed_modules' => $installed]);
        }
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

    // -------------------------------------------------------------------
    // UiKitUser trait generation
    //
    // Instead of asking the user to add 2 traits + 2 methods to their
    // User model (one set per relevant module), we generate a single
    // App\Models\Concerns\UiKitUser trait that bundles whatever is
    // currently installed. The user adds `use UiKitUser;` once.
    // -------------------------------------------------------------------

    /**
     * Write app/Models/Concerns/UiKitUser.php based on which kit modules are
     * currently installed. Idempotent — overwrites every call.
     */
    protected function generateUiKitUserTrait(): void
    {
        $installed = config('ui-kit.installed_modules', []);
        $hasAdmin = in_array('admin-middleware', $installed, true);
        $hasImpersonate = in_array('impersonation', $installed, true);

        if (! $hasAdmin && ! $hasImpersonate) {
            return; // no relevant modules → no trait needed
        }

        $dir = app_path('Models/Concerns');
        $path = $dir.'/UiKitUser.php';

        if (! is_dir($dir)) {
            (new Filesystem())->ensureDirectoryExists($dir);
        }

        $imports = [];
        $traitUses = [];
        $methods = [];

        if ($hasAdmin) {
            $imports[] = 'use Spatie\\Permission\\Traits\\HasRoles;';
            $traitUses[] = 'use HasRoles;';
        }

        if ($hasImpersonate) {
            $imports[] = 'use Lab404\\Impersonate\\Models\\Impersonate;';
            $traitUses[] = 'use Impersonate;';
            $methods[] = <<<'PHP'

    public function canImpersonate(): bool
    {
        return method_exists($this, 'hasRole')
            ? (bool) $this->hasRole('admin')
            : (bool) ($this->is_admin ?? false);
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->canImpersonate();
    }
PHP;
        }

        $importsBlock = implode("\n", $imports);
        $usesBlock = $traitUses === [] ? '' : '    '.implode("\n    ", $traitUses)."\n";
        $methodsBlock = implode("\n", $methods);

        $contents = <<<PHP
<?php

namespace App\Models\Concerns;

{$importsBlock}

/**
 * Bundles the kit's User-model requirements based on which modules are
 * currently installed. Regenerated by ui-kit:install — do not hand-edit
 * (your changes will be overwritten on the next install / module add).
 *
 * Usage in app/Models/User.php:
 *
 *     use App\\Models\\Concerns\\UiKitUser;
 *
 *     class User extends Authenticatable {
 *         use UiKitUser;
 *     }
 */
trait UiKitUser
{
{$usesBlock}{$methodsBlock}
}
PHP;

        file_put_contents($path, rtrim($contents, "\n")."\n");
        $this->line('  ✓ generated <info>app/Models/Concerns/UiKitUser.php</info>');
    }

    // -------------------------------------------------------------------
    // Host-file patching
    // -------------------------------------------------------------------

    /**
     * Append nav entries into config/admin.php between
     * /* ui-kit:nav-start *\/ ... /* ui-kit:nav-end *\/ markers.
     *
     * Idempotent: skips entries whose 'route' key already appears anywhere
     * in the existing nav array.
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    protected function patchAdminNav(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        $path = config_path('admin.php');

        if (! file_exists($path)) {
            $this->warn('config/admin.php not found; skipping nav patch.');

            return;
        }

        $contents = file_get_contents($path);
        $startMarker = '/* ui-kit:nav-start */';
        $endMarker = '/* ui-kit:nav-end */';

        if (! str_contains($contents, $startMarker) || ! str_contains($contents, $endMarker)) {
            $this->warn('config/admin.php is missing the ui-kit:nav markers; skipping nav patch. Add them manually or re-publish ui-kit-config.');

            return;
        }

        // Build the lines we want to add, skipping any whose route already exists.
        $existingRoutes = [];
        if (preg_match_all("/'route'\\s*=>\\s*'([^']+)'/", $contents, $matches)) {
            $existingRoutes = $matches[1];
        }

        $toInject = [];
        foreach ($entries as $entry) {
            $route = $entry['route'] ?? null;
            if ($route !== null && in_array($route, $existingRoutes, true)) {
                continue;
            }
            $toInject[] = $entry;
        }

        if (empty($toInject)) {
            return;
        }

        $rendered = '';
        foreach ($toInject as $entry) {
            $rendered .= "        ".$this->varExport($entry, 2).",\n";
        }

        // Preserve whatever earlier installs put between the markers — the
        // block is replaced wholesale, so dropping the existing body would
        // wipe other modules' nav entries.
        $existingBody = rtrim(ltrim($this->extractMarkerBlock($contents, $startMarker, $endMarker), "\n"));
        $injected = ($existingBody !== '' ? $existingBody."\n" : '').rtrim($rendered, "\n");

        $replacement = $startMarker."\n".$injected."\n        ".$endMarker;
        $pattern = '/'.preg_quote($startMarker, '/').'.*?'.preg_quote($endMarker, '/').'/s';
        $patched = preg_replace($pattern, $replacement, $contents);

        file_put_contents($path, $patched);
        $this->line('  ✓ patched <info>config/admin.php</info> nav (+'.count($toInject).')');
    }

    /**
     * Inject route lines into routes/admin.php between the ui-kit:admin-routes
     * markers. Idempotent by route-name string match within the marker block.
     *
     * @param  array<int, string>  $lines
     */
    protected function patchAdminRoutes(array $lines): void
    {
        $this->patchRoutesFile(
            base_path('routes/admin.php'),
            '/* ui-kit:admin-routes-start */',
            '/* ui-kit:admin-routes-end */',
            $lines,
            indent: 8
        );
    }

    /**
     * Inject route lines into routes/ui-kit-user.php (if published).
     *
     * @param  array<int, string>  $lines
     */
    protected function patchUserRoutes(array $lines): void
    {
        $this->patchRoutesFile(
            base_path('routes/ui-kit-user.php'),
            '/* ui-kit:user-routes-start */',
            '/* ui-kit:user-routes-end */',
            $lines,
            indent: 4
        );
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function patchRoutesFile(string $path, string $startMarker, string $endMarker, array $lines, int $indent): void
    {
        if (empty($lines)) {
            return;
        }

        if (! file_exists($path)) {
            $this->warn(basename($path)." not found; skipping route patch.");

            return;
        }

        $contents = file_get_contents($path);

        if (! str_contains($contents, $startMarker) || ! str_contains($contents, $endMarker)) {
            $this->warn(basename($path)." is missing ui-kit route markers; skipping patch.");

            return;
        }

        $pad = str_repeat(' ', $indent);
        $existing = $this->extractMarkerBlock($contents, $startMarker, $endMarker);

        $toInject = [];
        foreach ($lines as $line) {
            $route = $this->extractRouteName($line);
            if ($route !== null && str_contains($existing, "'".$route."'")) {
                continue;
            }
            if ($route === null && str_contains($existing, $line)) {
                continue;
            }
            $toInject[] = $pad.$line;
        }

        if (empty($toInject)) {
            return;
        }

        // ltrim only newlines so the first existing line keeps its indentation.
        $existingBody = rtrim(ltrim($existing, "\n"));
        $injected = ($existingBody !== '' ? $existingBody."\n" : '')
            .implode("\n", $toInject);

        $replacement = $startMarker."\n".$injected."\n".$pad.$endMarker;
        $pattern = '/'.preg_quote($startMarker, '/').'.*?'.preg_quote($endMarker, '/').'/s';
        $patched = preg_replace($pattern, $replacement, $contents);

        file_put_contents($path, $patched);
        $this->line('  ✓ patched <info>'.basename($path).'</info> (+'.count($toInject).' route'.(count($toInject) === 1 ? '' : 's').')');
    }

    protected function extractMarkerBlock(string $contents, string $startMarker, string $endMarker): string
    {
        $startPos = strpos($contents, $startMarker);
        $endPos = strpos($contents, $endMarker);

        if ($startPos === false || $endPos === false) {
            return '';
        }

        $startPos += strlen($startMarker);

        return substr($contents, $startPos, $endPos - $startPos);
    }

    protected function extractRouteName(string $line): ?string
    {
        if (preg_match("/->name\\(\\s*'([^']+)'\\s*\\)/", $line, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Swap the admin middleware fallback for the Spatie-backed real one.
     * Idempotent — if the swap has already happened, no-op.
     */
    protected function patchAdminMiddleware(): void
    {
        $path = config_path('admin.php');

        if (! file_exists($path)) {
            $this->warn('config/admin.php not found; skipping middleware patch.');

            return;
        }

        $contents = file_get_contents($path);
        $fallback = '\\Shipbytes\\UiKit\\Http\\Middleware\\EnsureIsAdminFallback::class';
        $real = '\\App\\Http\\Middleware\\EnsureUserIsAdmin::class';

        if (str_contains($contents, $real)) {
            return; // already swapped
        }

        if (! str_contains($contents, $fallback)) {
            $this->warn("Couldn't find the EnsureIsAdminFallback reference in config/admin.php; skipping middleware swap.");

            return;
        }

        $patched = str_replace($fallback, $real, $contents);
        file_put_contents($path, $patched);
        $this->line('  ✓ patched <info>config/admin.php</info> middleware (fallback → EnsureUserIsAdmin)');
    }

    // -------------------------------------------------------------------
    // Deferred command runners
    //
    // Modules accumulate vendor:publish / seed / npm / storage-link / migrate
    // requests during install. Run them all in one batch at the end, so we
    // don't migrate seven times when seven modules each shipped migrations.
    // -------------------------------------------------------------------

    /**
     * @param  array<string, string>  $args
     */
    protected function deferVendorPublish(array $args): void
    {
        $key = json_encode($args);
        if (! in_array($key, static::$deferredVendorPublishes, true)) {
            static::$deferredVendorPublishes[] = $key;
        }
    }

    protected function deferSeeder(string $class): void
    {
        if (! in_array($class, static::$deferredSeeders, true)) {
            static::$deferredSeeders[] = $class;
        }
    }

    protected function deferStorageLink(): void
    {
        static::$deferredStorageLink = true;
    }

    protected function deferMigrate(): void
    {
        static::$deferredMigrate = true;
    }

    /**
     * Drain all deferred commands. Safe to call when nothing was deferred.
     * Resets the deferred state after running so re-runs in the same process
     * (e.g. tests) don't double-execute.
     */
    protected function runDeferredCommands(): void
    {
        foreach (static::$deferredVendorPublishes as $argsJson) {
            $args = (array) json_decode($argsJson, true);
            Artisan::call('vendor:publish', $args);
            $label = $args['--provider'] ?? ($args['--tag'] ?? 'vendor:publish');
            $this->line("  ✓ published <info>{$label}</info>");
        }

        if (static::$deferredMigrate) {
            $this->line('  · migrating database...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line('  ✓ <info>migrate</info> done');
        }

        foreach (static::$deferredSeeders as $class) {
            Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
            $this->line("  ✓ seeded <info>{$class}</info>");
        }

        if (static::$deferredStorageLink) {
            Artisan::call('storage:link');
            $this->line('  ✓ <info>storage:link</info> created');
        }

        $this->resetDeferred();
    }
}
