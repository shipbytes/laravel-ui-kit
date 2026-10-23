<?php

namespace Shipbytes\UiKit\Tests\Feature\Changelog;

use App\Livewire\Changelog\PublicChangelog;
use App\Models\ChangelogEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mews\Purifier\PurifierServiceProvider;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * The changelog module's public half: /changelog, for anyone.
 */
class PublicChangelogTest extends InstalledKitTestCase
{
    protected string $modules = 'changelog';

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [PurifierServiceProvider::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-20 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_guest_reads_published_entries_grouped_by_month_newest_first(): void
    {
        $this->entry('Scheduled exports', 'feature', '2026-10-14');
        $this->entry('Faster dashboard', 'improvement', '2026-10-02');
        $this->entry('VAT rounding', 'fix', '2026-09-21');
        $this->entry('Okta sign-in', 'feature', '2026-08-03');
        $this->entry('Secret draft', 'feature', '2026-10-10', published: false);
        $this->entry('Next week', 'feature', '2026-10-27');

        $this->get('/changelog')
            ->assertOk()
            ->assertSeeInOrder(['October 2026', 'Scheduled exports', 'Faster dashboard', 'September 2026', 'VAT rounding', 'August 2026', 'Okta sign-in'])
            ->assertDontSee('Secret draft')
            ->assertDontSee('Next week');
    }

    public function test_an_entry_dated_today_is_published_today(): void
    {
        // Saved through the model, the way the admin form saves it.
        $this->entry('Out this morning', 'feature', '2026-10-20');
        $this->entry('Out tomorrow', 'feature', '2026-10-21');

        $this->get('/changelog')
            ->assertSee('Out this morning')
            ->assertDontSee('Out tomorrow');

        $this->assertSame(['Out this morning'], ChangelogEntry::published()->pluck('title')->all());
    }

    public function test_the_category_filter_lives_in_the_query_string_with_counts(): void
    {
        $this->entry('Scheduled exports', 'feature', '2026-10-14');
        $this->entry('Okta sign-in', 'feature', '2026-08-03');
        $this->entry('VAT rounding', 'fix', '2026-09-21');
        $this->entry('Unpublished fix', 'fix', '2026-09-22', published: false);

        $page = $this->get('/changelog?category=fix')
            ->assertOk()
            ->assertSee('VAT rounding')
            ->assertDontSee('Scheduled exports');

        // Links, each its own URL, the current one marked; categories with
        // nothing published get no link.
        $html = (string) $page->getContent();
        $this->assertStringContainsString('aria-label="Filter by category"', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*\/changelog\?category=fix"[^>]*aria-current="page"/', $html);
        $this->assertStringNotContainsString('category=improvement', $html);

        Livewire::test(PublicChangelog::class)
            ->assertViewHas('counts', fn ($counts) => $counts->all() === ['feature' => 2, 'fix' => 1])
            ->set('category', 'feature')
            ->assertSee('Okta sign-in')
            ->assertDontSee('VAT rounding');
    }

    public function test_the_counts_are_one_query(): void
    {
        $this->entry('Scheduled exports', 'feature', '2026-10-14');
        $this->entry('VAT rounding', 'fix', '2026-09-21');

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(PublicChangelog::class);
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $this->assertCount(1, array_filter($queries, fn (string $sql) => str_contains($sql, 'count(*)')), implode("\n", $queries));
    }

    public function test_an_unknown_category_shows_everything(): void
    {
        $this->entry('Scheduled exports', 'feature', '2026-10-14');
        $this->entry('VAT rounding', 'fix', '2026-09-21');

        $this->get('/changelog?category=nonsense')
            ->assertOk()
            ->assertSee('Scheduled exports')
            ->assertSee('VAT rounding');
    }

    public function test_the_body_is_rendered_as_the_purified_html_it_was_saved_as(): void
    {
        $this->entry('Scheduled exports', 'feature', '2026-10-14', body: '<p>Reports go out <strong>on a schedule</strong>.</p>');

        $this->get('/changelog')
            ->assertSee('<p>Reports go out <strong>on a schedule</strong>.</p>', false)
            ->assertDontSee('&lt;strong&gt;', false);
    }

    public function test_an_empty_changelog_says_so(): void
    {
        $this->entry('Secret draft', 'feature', '2026-10-10', published: false);

        $this->get('/changelog')
            ->assertOk()
            ->assertSee('Nothing published yet')
            ->assertDontSee('Filter by category');
    }

    private function entry(string $title, string $category, string $date, bool $published = true, ?string $body = null): ChangelogEntry
    {
        return ChangelogEntry::create([
            'title' => $title,
            'body' => $body ?? "<p>{$title}.</p>",
            'category' => $category,
            'published_at' => $date,
            'is_published' => $published,
        ]);
    }
}
