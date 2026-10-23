<?php

namespace Database\Seeders;

use App\Models\ChangelogEntry;
use App\Models\ContactSubmission;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

/**
 * Data behind the documentation screenshots (scripts/screenshots.mjs copies this into the
 * throwaway app and runs it after `migrate:fresh`). Deterministic on purpose: the same
 * names and counts every run, so a regenerated image only changes when the UI does.
 *
 * Two accounts matter to the harness: admin@example.com (admin, no 2FA) and
 * grace@example.com (2FA enabled and confirmed). Both use the password "password".
 * margaret@example.com is an admin too, only so the user list shows the badge.
 */
class ScreenshotSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(20261020);

        $admin = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now()->subMonths(8),
            'created_at' => now()->subMonths(8),
        ]);
        Artisan::call('ui-kit:make-admin', ['email' => 'admin@example.com']);

        $grace = User::factory()->create([
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now()->subMonths(5),
            'created_at' => now()->subMonths(5),
        ]);
        app(EnableTwoFactorAuthentication::class)($grace);
        $grace->forceFill(['two_factor_confirmed_at' => now()->subMonths(5)])->save();

        $people = [
            ['Linus Torvalds', 'linus@example.com'], ['Margaret Hamilton', 'margaret@example.com'],
            ['Dennis Ritchie', 'dennis@example.com'], ['Barbara Liskov', 'barbara@example.com'],
            ['Ken Thompson', 'ken@example.com'], ['Radia Perlman', 'radia@example.com'],
            ['Guido van Rossum', 'guido@example.com'], ['Frances Allen', 'frances@example.com'],
            ['Bjarne Stroustrup', 'bjarne@example.com'], ['Anita Borg', 'anita@example.com'],
            ['Tim Berners-Lee', 'tim@example.com'], ['Hedy Lamarr', 'hedy@example.com'],
        ];
        $users = collect($people)->map(fn (array $p, int $i) => User::factory()->create([
            'name' => $p[0],
            'email' => $p[1],
            'password' => Hash::make('password'),
            'email_verified_at' => $i % 4 === 3 ? null : now()->subDays($i * 9 + 2),
            'created_at' => now()->subDays($i * 9 + 2),
        ]));

        // A second admin near the top of the user list, so its Admin badge is in the shot.
        Artisan::call('ui-kit:make-admin', ['email' => 'margaret@example.com']);

        $tickets = [
            ['Cannot export the monthly report as CSV', 'bug', 'high', 'open'],
            ['Invoice PDF shows the wrong VAT rate', 'billing', 'high', 'open'],
            ['Add a dark mode to the customer portal', 'feature_request', 'low', 'open'],
            ['Two-factor codes rejected after a phone change', 'general', 'medium', 'in_progress'],
            ['Webhook retries stop after three attempts', 'bug', 'medium', 'in_progress'],
            ['Request: SSO through Okta', 'feature_request', 'low', 'closed'],
            ['Charged twice for the March renewal', 'billing', 'high', 'resolved'],
            ['Search ignores accented characters', 'bug', 'low', 'closed'],
        ];
        foreach ($tickets as $i => [$subject, $category, $priority, $status]) {
            $ticket = SupportTicket::factory()->create([
                'user_id' => $users[$i]->id,
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => $status,
                'closed_at' => $status === 'closed' ? now()->subDays($i) : null,
                'created_at' => now()->subDays($i * 2 + 1),
                'updated_at' => now()->subDays($i * 2)->subHours($i + 2),
            ]);
            TicketReply::factory()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $users[$i]->id,
                'message' => 'Steps to reproduce: open the dashboard, pick last month, press Export. The download starts and then stops at 0 bytes.',
                'created_at' => now()->subDays($i * 2 + 1),
            ]);
            TicketReply::factory()->fromAdmin()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $admin->id,
                'message' => 'Thanks, reproduced on our side. A fix is in review and should ship with the next release.',
                'created_at' => now()->subDays($i * 2),
            ]);
        }

        $entries = [
            ['Scheduled exports', 'feature', 'Reports can now be emailed on a daily, weekly or monthly schedule.'],
            ['Faster dashboard loads', 'improvement', 'The overview now renders in under 200 ms on accounts with more than 50,000 records.'],
            ['Fixed VAT rounding on invoices', 'fix', 'Totals are rounded once per invoice rather than per line, matching the PDF.'],
            ['Webhook delivery log', 'feature', 'Every webhook attempt is listed with its response code and can be replayed.'],
            ['Accented search', 'fix', 'Search now matches names regardless of accents.'],
            ['Okta single sign-on', 'feature', 'Teams on the Business plan can sign in through Okta.'],
        ];
        foreach ($entries as $i => [$title, $category, $body]) {
            ChangelogEntry::factory()->create([
                'title' => $title,
                'category' => $category,
                'body' => "<p>{$body}</p>",
                'published_at' => now()->subWeeks($i + 1),
                'is_published' => true,
            ]);
        }
        ChangelogEntry::factory()->draft()->create([
            'published_at' => now()->addWeek(),
            'title' => 'Customer portal dark mode',
            'category' => 'feature',
            'body' => '<p>Draft notes for the dark-mode release.</p>',
        ]);

        $contacts = [
            ['Priya Natarajan', 'priya@example.com', 'Enterprise pricing', 'unread', false],
            ['Marcus Chen', 'marcus@example.com', 'Partnership proposal', 'unread', false],
            ['Elena Petrova', 'elena@example.com', 'Data residency in the EU', 'unread', false],
            ['Samuel Okafor', 'samuel@example.com', 'Press enquiry', 'read', false],
            ['Yuki Tanaka', 'yuki@example.com', 'Invoice copy for 2025', 'replied', false],
            ['Lucía Fernández', 'lucia@example.com', 'Accessibility statement', 'replied', false],
            ['Noah Williams', 'noah@example.com', 'Student discount', 'read', true],
        ];
        // Archiving sets archived_at; it is not a status.
        foreach ($contacts as $i => [$name, $email, $subject, $status, $archived]) {
            ContactSubmission::factory()->create([
                'reply' => $status === 'replied' ? "Thanks {$name}, details are on their way by email." : null,
                'replied_at' => $status === 'replied' ? now()->subDays($i) : null,
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => "Hello,\n\nCould you tell me more about {$subject}? We are evaluating the product for a team of twelve.\n\nThanks,\n{$name}",
                'status' => $status,
                'archived_at' => $archived ? now()->subDays($i) : null,
                'created_at' => now()->subDays($i + 1),
            ]);
        }

        if (function_exists('activity')) {
            // A few log streams, so the viewer's log-name badges and its
            // "Log stream" filter have something to show.
            $events = [
                ['updated', $users[0], ['attributes' => ['name' => 'Linus Torvalds'], 'old' => ['name' => 'L. Torvalds']], 'users'],
                ['created', $users[11], ['attributes' => ['email' => 'hedy@example.com']], 'users'],
                ['updated', $users[3], ['attributes' => ['email_verified_at' => null]], 'users'],
                ['deleted', $users[7], [], 'default'],
                ['updated', $grace, ['attributes' => ['two_factor_confirmed_at' => now()->toDateTimeString()]], 'auth'],
            ];
            foreach ($events as $i => [$event, $subject, $properties, $log]) {
                activity($log)
                    ->causedBy($i % 2 ? $admin : $subject)
                    ->performedOn($subject)
                    ->event($event)
                    ->withProperties($properties)
                    ->createdAt(now()->subHours($i * 7 + 1)->subMinutes($i * 13))
                    ->log($event);
            }
        }
    }
}
