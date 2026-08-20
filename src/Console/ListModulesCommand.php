<?php

namespace Shipbytes\UiKit\Console;

use Illuminate\Console\Command;
use Shipbytes\UiKit\Support\ModuleRegistry;

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

            $rows[] = [$slug, $meta['label'], $status];
        }

        $this->table(['Slug', 'Name', 'Status'], $rows);

        return self::SUCCESS;
    }
}
