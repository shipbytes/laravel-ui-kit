<?php

namespace Shipbytes\UiKit\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Every published view is built on the Shipbytes design system: ds::
 * components and semantic tokens, nothing else. This is the guard that makes
 * that a property of the suite rather than a habit. A stub fails when it
 * reaches for a raw palette colour, a dark: variant (the tokens flip
 * themselves), an inline <svg> (icons go through ds::icon) or a raw form
 * control (ds::button, ds::input, ds::select, ds::checkbox…).
 *
 * When the design system lacks something a stub needs, the gap is fixed
 * upstream in shipbytes/design-system, not worked around here.
 */
class StubStyleTest extends TestCase
{
    /**
     * Screens not yet moved onto the design system. Phases 3 and 4 of the
     * 0.4.0 series empty this list; nothing should ever be added back to it.
     */
    private const NOT_YET_MIGRATED = [
        'stubs/core/views/livewire/admin/dashboard.blade.php',
        'stubs/core/views/livewire/admin/users/user-detail.blade.php',
        'stubs/core/views/livewire/admin/users/user-list.blade.php',
        'stubs/modules/activity-log/views/livewire/admin/activity/activity-viewer.blade.php',
        'stubs/modules/changelog/views/livewire/admin/changelog/changelog-form.blade.php',
        'stubs/modules/changelog/views/livewire/admin/changelog/changelog-list.blade.php',
        'stubs/modules/contacts/views/livewire/admin/contacts/contact-list.blade.php',
        'stubs/modules/support-tickets/views/livewire/admin/support/ticket-detail.blade.php',
        'stubs/modules/support-tickets/views/livewire/admin/support/ticket-list.blade.php',
    ];

    /** @var array<string, string> rule => pattern */
    private const RULES = [
        'palette utility' => '/\b(?:bg|text|ring|border|from|via|to|fill|stroke|outline|shadow|decoration|placeholder|divide|accent|caret)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}\b/',
        'dark: variant' => '/(?<![\w-])dark:[\w\[]/',
        'inline <svg>' => '/<svg\b/i',
        'raw <button>' => '/<button\b/i',
        'raw <input>' => '/<input\b(?![^>]*\btype\s*=\s*["\']hidden["\'])/i',
        'raw <select>' => '/<select\b/i',
        'raw <textarea>' => '/<textarea\b/i',
    ];

    public function test_stubs_are_built_on_the_design_system(): void
    {
        $offenders = [];

        foreach ($this->stubs() as $relative => $path) {
            if (in_array($relative, self::NOT_YET_MIGRATED, true)) {
                continue;
            }

            // Matched against the whole file, not line by line, so a tag whose
            // attributes span several lines (<input … type="hidden">) is read whole.
            $contents = (string) file_get_contents($path);
            $lines = explode("\n", $contents);
            $hits = [];

            foreach (self::RULES as $rule => $pattern) {
                preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE);

                foreach ($matches[0] as [, $offset]) {
                    $line = substr_count($contents, "\n", 0, $offset);
                    $hits[$line] ??= sprintf('  line %d (%s): %s', $line + 1, $rule, trim($lines[$line]));
                }
            }

            if ($hits !== []) {
                ksort($hits);
                $offenders[] = $relative."\n".implode("\n", array_slice($hits, 0, 3));
            }
        }

        $this->assertSame([], $offenders, "These stubs style themselves instead of using ds:: components and semantic tokens:\n\n"
            .implode("\n\n", $offenders)
            ."\n\nUse a ds:: component or a semantic token (bg-surface, text-fg-muted, border-border…). If the design system cannot express it, report the gap upstream.");
    }

    public function test_the_not_yet_migrated_list_does_not_rot(): void
    {
        $missing = array_values(array_filter(
            self::NOT_YET_MIGRATED,
            fn (string $relative) => ! is_file($this->root().'/'.$relative)
        ));

        $this->assertSame([], $missing, 'Allowlisted stubs that no longer exist; remove them from StubStyleTest::NOT_YET_MIGRATED.');
    }

    public function test_the_rules_catch_what_they_claim_to(): void
    {
        $bad = [
            'palette utility' => '<div class="bg-zinc-100">',
            'dark: variant' => '<div class="dark:bg-surface">',
            'inline <svg>' => '<svg viewBox="0 0 24 24">',
            'raw <button>' => '<button type="submit">',
            'raw <input>' => '<input type="text" name="q">',
            'raw <select>' => '<select name="role">',
            'raw <textarea>' => '<textarea name="body">',
        ];

        foreach ($bad as $rule => $line) {
            $this->assertMatchesRegularExpression(self::RULES[$rule], $line, $rule);
        }

        foreach ([
            '<x-ds::button variant="ghost">',
            '<input type="hidden" name="_token">',
            '<div class="bg-surface text-fg-muted border-border bg-accent-wash text-on-accent">',
            "x-show=\"\$store.theme.value === 'dark'\"",
        ] as $line) {
            foreach (self::RULES as $rule => $pattern) {
                $this->assertDoesNotMatchRegularExpression($pattern, $line, "{$rule} must not flag: {$line}");
            }
        }
    }

    /** @return array<string, string> repo-relative path => absolute path */
    private function stubs(): array
    {
        $root = $this->root();
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/stubs', \FilesystemIterator::SKIP_DOTS));
        $stubs = [];

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $stubs[str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))] = $file->getPathname();
            }
        }

        ksort($stubs);

        $this->assertNotEmpty($stubs, 'No Blade stubs found; is the stubs/ path right?');

        return $stubs;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
