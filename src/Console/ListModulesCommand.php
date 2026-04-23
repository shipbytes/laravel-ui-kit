<?php

namespace Shipbytes\UiKit\Console;

use Shipbytes\UiKit\Support\ModuleRegistry;
use Illuminate\Console\Command;

class ListModulesCommand extends Command
{
    protected $signature = 'ui-kit:list-modules';

    protected $description = 'List all UI Kit modules with their installation status.';

    public function handle(ModuleRegistry $registry): int
    {
        $rows = [];

        foreach ($registry->all() as $slug => $meta) {
            $installed = $registry->isInstalled($slug);
            $status = $installed ? '<info>installed</info>' : '<comment>available</comment>';

            if (! empty($meta['providers'])) {
                $enabled = config("ui-kit.installed_modules.{$slug}", []);
                $status .= ' ('.(empty($enabled) ? 'no providers' : implode(', ', $enabled)).')';
            }

            $rows[] = [$slug, $meta['label'], $status];
        }

        $this->table(['Slug', 'Name', 'Status'], $rows);

        return self::SUCCESS;
    }
}
