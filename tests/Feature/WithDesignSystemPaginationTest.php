<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Livewire\Component;
use Livewire\WithPagination;
use Shipbytes\UiKit\Livewire\Concerns\WithDesignSystemPagination;
use Shipbytes\UiKit\Tests\TestCase;

class WithDesignSystemPaginationTest extends TestCase
{
    public function test_it_names_the_design_systems_livewire_views(): void
    {
        $component = new class extends Component
        {
            use WithDesignSystemPagination;

            public function render(): string
            {
                return '<div></div>';
            }
        };

        $this->assertSame('ds::pagination-livewire', $component->paginationView());
        $this->assertSame('ds::simple-pagination-livewire', $component->paginationSimpleView());

        // It IS WithPagination: gotoPage, resetPage and the paginators state
        // come with it, so swapping it in for the stock trait changes nothing else.
        $this->assertContains(WithPagination::class, class_uses_recursive($component));
    }

    public function test_an_app_that_overrides_the_view_keeps_its_own(): void
    {
        $component = new class extends Component
        {
            use WithDesignSystemPagination;

            public function paginationView(): string
            {
                return 'vendor.pagination.mine';
            }

            public function render(): string
            {
                return '<div></div>';
            }
        };

        $this->assertSame('vendor.pagination.mine', $component->paginationView());
        $this->assertSame('ds::simple-pagination-livewire', $component->paginationSimpleView());
    }
}
