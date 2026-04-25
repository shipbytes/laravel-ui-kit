<?php

namespace Shipbytes\UiKit\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UiKitBanners extends Component
{
    public function render(): View
    {
        return view('ui-kit::components.ui-kit-banners');
    }
}
