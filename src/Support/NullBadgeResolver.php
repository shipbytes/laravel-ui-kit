<?php

namespace Shipbytes\UiKit\Support;

use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;

class NullBadgeResolver implements SidebarBadgeResolver
{
    public function counts(): array
    {
        return [];
    }
}
