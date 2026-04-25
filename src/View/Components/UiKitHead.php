<?php

namespace Shipbytes\UiKit\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UiKitHead extends Component
{
    public function render(): View
    {
        return view('ui-kit::components.ui-kit-head');
    }
}
