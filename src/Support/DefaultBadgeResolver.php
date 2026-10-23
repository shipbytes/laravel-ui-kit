<?php

namespace Shipbytes\UiKit\Support;

use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;

/**
 * The counts behind the support-tickets and contacts nav badges, bound by
 * default so the badges work without any code in the app. An app that binds
 * its own SidebarBadgeResolver replaces this one entirely.
 *
 * Each count is there only when its module's model exists, and the layout
 * asks once per page (the rail and the phone bar share one call), so a page
 * costs at most two count queries.
 */
class DefaultBadgeResolver implements SidebarBadgeResolver
{
    /** The support-tickets module's model; a subclass may point elsewhere. */
    protected string $ticketModel = 'App\\Models\\SupportTicket';

    /** The contacts module's model. */
    protected string $contactModel = 'App\\Models\\ContactSubmission';

    /** @var array<string, int>|null */
    protected ?array $counts = null;

    /**
     * Why a count is missing, by badge key. The sidebar is on every admin
     * page, so a count that throws (a model published, its table never
     * migrated) is left out rather than failing all of them; ui-kit:doctor
     * reports it from here.
     *
     * @var array<string, string>
     */
    public array $failures = [];

    public function counts(): array
    {
        return $this->counts ??= $this->compute();
    }

    /**
     * @return array<string, int>
     */
    protected function compute(): array
    {
        $counts = [];

        $ticket = $this->ticketModel;

        if (class_exists($ticket)) {
            $this->count($counts, 'open_tickets', fn () => $ticket::open()->count());
        }

        $contact = $this->contactModel;

        if (class_exists($contact)) {
            // A model published before 0.4.1 has no unread scope.
            $this->count($counts, 'unread_contacts', fn () => method_exists($contact, 'scopeUnread')
                ? $contact::query()->unread()->count()
                : $contact::query()->whereNull('archived_at')->where('status', 'unread')->count());
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  callable(): int  $query
     */
    protected function count(array &$counts, string $key, callable $query): void
    {
        try {
            $counts[$key] = (int) $query();
        } catch (\Throwable $e) {
            $this->failures[$key] = $e->getMessage();
        }
    }
}
