<?php

namespace Shipbytes\UiKit\Livewire\Concerns;

use Livewire\WithPagination;

/**
 * Livewire pagination drawn by the design system.
 *
 * WithPagination swaps the paginator's default view for Livewire's own while a
 * component renders, so Paginator::defaultView() in a service provider is
 * ignored inside one. Livewire asks the component instead, through
 * method_exists(), for paginationView() and paginationSimpleView(). These
 * return blade-ui's Livewire variants, whose buttons call gotoPage, previousPage
 * and nextPage rather than following a link, so a page change keeps the
 * component's search and filters.
 *
 * A component that defines either method itself keeps its own: a class method
 * wins over a trait's.
 */
trait WithDesignSystemPagination
{
    use WithPagination;

    public function paginationView(): string
    {
        return 'ds::pagination-livewire';
    }

    public function paginationSimpleView(): string
    {
        return 'ds::simple-pagination-livewire';
    }
}
