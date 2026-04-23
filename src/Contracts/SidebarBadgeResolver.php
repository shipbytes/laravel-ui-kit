<?php

namespace Shipbytes\UiKit\Contracts;

interface SidebarBadgeResolver
{
    /**
     * Return an associative array of badge keys to integer counts.
     * Keys match the `badge` field on nav items in config/admin.php.
     *
     * @return array<string, int>
     */
    public function counts(): array;
}
