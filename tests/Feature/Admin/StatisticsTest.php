<?php

use App\Enums\PublicationStatus;
use App\Enums\UserRoles;
use App\Http\Controllers\Admin\StatisticsController;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\WoodSpecies;
use App\Models\State;
use App\Models\StatisticsSnapshot;
use App\Statistics\ActivityStats;
use App\Statistics\MillStats;
use App\Statistics\SimilarNames;
use App\Statistics\Snapshots;
use App\Statistics\StatsAccess;
use App\Statistics\SubmissionStats;
use App\Support\ActivitySource;
use Illuminate\Support\Facades\DB;

/**
 * Admin Statistics: who can see which page, and that the numbers behind the charts are right.
 */

beforeEach(function () {
    $this->georgia = State::factory()->create(['name' => 'Georgia', 'abbreviation' => 'GA']);
    $this->alabama = State::factory()->create(['name' => 'Alabama', 'abbreviation' => 'AL']);
});

/**
 * Set timestamps without model events, so nothing is logged and updated_at isn't bumped.
 */
function setMillDates(Mill $mill, array $dates): void
{
    DB::table('mills')->where('id', $mill->id)->update($dates);
}

describe('access', function () {
    test('every page needs statistics.see', function (string $path) {
        loginAdminUser(['mills.see']);

        $this->get("/admin/{$path}")->assertForbidden();
    })->with([
        'overview' => 'statistics',
        'by state' => 'statistics/state/GA',
        'submissions' => 'statistics/submissions',
        'activity' => 'statistics/activity',
        'similar names' => 'statistics/similar',
        'compare' => 'statistics/compare',
    ]);

    test('every page renders with data', function (string $path) {
        $mills = Mill::factory()->count(3)->for($this->georgia)->create();
        Mill::factory()->for($this->alabama)->create();
        MillEdit::factory()->for($mills[0])->withChanges(['email' => 'new@example.com'])->create();
        MillEdit::factory()->newBusiness(['state_id' => $this->georgia->id])->create(['status' => PublicationStatus::Rejected, 'reviewed_at' => now()]);
        loginAdminUser(['statistics.see', 'statistics.compare_states', 'mills.edit', 'activity_log.see']);
        $mills[1]->update(['mill_name' => 'Renamed']);

        $this->get("/admin/{$path}")->assertOk();
    })->with([
        'overview' => 'statistics',
        'by state' => 'statistics/state/GA',
        'by state, picked with the form' => 'statistics/state?state=AL',
        'submissions' => 'statistics/submissions',
        'submissions for a state' => 'statistics/submissions?state=GA',
        'activity' => 'statistics/activity',
        'activity for a state' => 'statistics/activity?state=GA',
        'similar names' => 'statistics/similar',
        'compare' => 'statistics/compare',
    ]);

    test('every page renders with no data at all', function (string $path) {
        loginAdminUser(['statistics.see', 'statistics.compare_states']);

        $this->get("/admin/{$path}")->assertOk();
    })->with([
        'overview' => 'statistics',
        'submissions' => 'statistics/submissions',
        'activity' => 'statistics/activity',
        'similar names' => 'statistics/similar',
        'compare' => 'statistics/compare',
    ]);

    test('By State with no states that have mills is a 404', function () {
        loginAdminUser(['statistics.see']);

        $this->get('/admin/statistics/state')->assertNotFound();
    });

    test('Compare States needs statistics.compare_states, and its tab only shows with it', function () {
        Mill::factory()->for($this->georgia)->create();
        loginAdminUser(['statistics.see']);

        $this->get('/admin/statistics/compare')->assertForbidden();
        $this->get('/admin/statistics')->assertOk()->assertDontSee('Compare States');
    });

    test('Superadmins get statistics.compare_states from the seed data', function () {
        $byRole = json_decode(file_get_contents(database_path('data/permissions-by-role.json')), true);

        expect($byRole[UserRoles::SUPER->value])->toContain(StatsAccess::COMPARE_PERMISSION)
            ->and($byRole[UserRoles::ADMIN->value])->not->toContain(StatsAccess::COMPARE_PERMISSION)
            ->and($byRole[UserRoles::AGENT->value])->not->toContain(StatsAccess::COMPARE_PERMISSION);
    });

    test('a State Agent sees their own state by default and can\'t open another', function () {
        Mill::factory()->for($this->georgia)->create(['mill_name' => 'Peach Lumber']);
        Mill::factory()->for($this->alabama)->create();
        loginStateAgent($this->georgia->id, ['statistics.see']);

        $this->get('/admin/statistics/state')->assertOk()->assertSee('Georgia')->assertSee('Peach Lumber');
        $this->get('/admin/statistics/state/AL')->assertForbidden();
        $this->get('/admin/statistics/state?state=AL')->assertForbidden();
    });

    test('a State Agent filtering another state\'s submissions gets the region-wide view instead', function () {
        loginStateAgent($this->georgia->id, ['statistics.see']);

        $access = StatsAccess::current();

        expect($access->filterState('AL'))->toBeNull()
            ->and($access->filterState('GA')?->id)->toBe($this->georgia->id)
            ->and($access->states()->pluck('abbreviation')->all())->toBe(['GA']);
    });

    test('an Administrator can open any state with mills, by abbreviation or id', function () {
        Mill::factory()->for($this->georgia)->create();
        Mill::factory()->for($this->alabama)->create();
        loginAdminUser(['statistics.see'], UserRoles::ADMIN);

        $access = StatsAccess::current();

        expect($access->resolveState('ga')?->id)->toBe($this->georgia->id)
            ->and($access->resolveState((string) $this->alabama->id)?->id)->toBe($this->alabama->id)
            ->and($access->resolveState(null)?->name)->toBe('Alabama') // first alphabetically
            ->and($access->resolveState('TX'))->toBeNull();
    });
});

describe('mill numbers', function () {
    test('completeness counts blank strings as missing and either email column as having an email', function () {
        $type = MillType::factory()->create();
        $full = Mill::factory()->for($this->georgia)->create([
            'email' => 'a@example.com', 'web_site' => 'https://example.com', 'contact_name' => 'Pat', 'size' => 'Large',
        ]);
        $full->millTypes()->attach($type);
        $full->woodSpecies()->attach(\App\Models\WoodSpecies::factory()->create());
        Mill::factory()->for($this->georgia)->create(['email' => '  ', 'email_2' => 'b@example.com', 'web_site' => '', 'contact_name' => null]);
        Mill::factory()->for($this->georgia)->create(['email' => '', 'email_2' => null, 'web_site' => null]);
        Mill::factory()->for($this->georgia)->pending()->create(['email' => 'hidden@example.com']);

        $completeness = MillStats::for($this->georgia)->completeness();

        expect($completeness['total'])->toBe(3)
            ->and($completeness['fields']['email']['count'])->toBe(2)
            ->and($completeness['fields']['email']['percent'])->toBe(66.7)
            ->and($completeness['fields']['website']['count'])->toBe(1)
            ->and($completeness['fields']['types']['count'])->toBe(1)
            ->and($completeness['complete'])->toBe($full->fresh()->county_id ? 1 : 0);
    });

    test('completeness by state keeps states apart', function () {
        Mill::factory()->for($this->georgia)->create(['email' => 'a@example.com']);
        Mill::factory()->for($this->alabama)->count(2)->create(['email' => null, 'email_2' => null]);

        $byState = MillStats::completenessByState();

        expect($byState[$this->georgia->id]['fields']['email']['count'])->toBe(1)
            ->and($byState[$this->alabama->id]['total'])->toBe(2)
            ->and($byState[$this->alabama->id]['fields']['email']['count'])->toBe(0);
    });

    test('data age puts each mill in one bucket by when it was last updated', function () {
        foreach ([5, 45, 120, 200, 400, 800] as $days) {
            setMillDates(Mill::factory()->for($this->georgia)->create(), ['updated_at' => now()->subDays($days)]);
        }

        expect(MillStats::for($this->georgia)->dataAge())->toBe([
            'Within 30 days' => 1,
            '31–90 days' => 1,
            '91–180 days' => 1,
            '181–365 days' => 1,
            'Over a year' => 2,
        ]);
    });

    test('recent changes count mills added and updated in each window, by state', function () {
        $new = Mill::factory()->for($this->georgia)->create();
        $old = Mill::factory()->for($this->georgia)->create();
        setMillDates($old, ['created_at' => now()->subDays(500), 'updated_at' => now()->subDays(100)]);

        $georgia = MillStats::recentChangesByState()['Georgia'];

        expect($georgia['mills'])->toBe(2)
            ->and($georgia['added'])->toBe([30 => 1, 90 => 1, 180 => 1, 365 => 1])
            ->and($georgia['updated'])->toBe([30 => 1, 90 => 1, 180 => 2, 365 => 2]);
    });

    test('the county list includes counties with no mills and builds 5-digit FIPS codes', function () {
        $empty = County::forceCreate(['name' => 'Empty', 'state_id' => $this->georgia->id, 'state_code' => '13', 'fips_code' => '1']);
        $busy = County::forceCreate(['name' => 'Busy', 'state_id' => $this->georgia->id, 'state_code' => '13', 'fips_code' => '121']);
        Mill::factory()->for($this->georgia)->count(2)->create(['county_name' => 'Busy', 'county_id' => $busy->id]);

        $counties = MillStats::for($this->georgia)->byCounty()->keyBy('name');

        expect($counties['Busy'])->toBe(['fips' => '13121', 'name' => 'Busy', 'mills' => 2])
            ->and($counties['Empty'])->toBe(['fips' => '13001', 'name' => 'Empty', 'mills' => 0])
            ->and(MillStats::fips(null, '1'))->toBeNull();
    });

    test('long lists fold into "All others" without swallowing the "Other" mill type', function () {
        $counts = ['Sawmill' => 9, 'Other' => 5, 'Pallet' => 3, 'Pulp' => 2, 'Chip' => 1];

        expect(MillStats::topWithOther($counts, 2))->toBe(['Sawmill' => 9, 'Other' => 5, 'All others' => 6])
            ->and(MillStats::topWithOther($counts, 4))->toBe($counts);
    });

    test('map shading bins cover 1 to the busiest county', function () {
        expect(StatisticsController::bins(0))->toBe([])
            ->and(StatisticsController::bins(2))->toBe([[1, 1], [2, 2]])
            ->and(StatisticsController::bins(10))->toBe([[1, 3], [4, 6], [7, 9], [10, 10]]);
    });
});

describe('snapshots', function () {
    test('a snapshot records each state\'s mills, completeness and types, once per day', function () {
        $sawmill = MillType::factory()->create(['name' => 'Sawmill']);
        Mill::factory()->for($this->georgia)->count(2)->create(['email' => 'a@example.com'])
            ->each(fn (Mill $mill) => $mill->millTypes()->attach($sawmill));
        Mill::factory()->for($this->alabama)->create(['email' => null, 'email_2' => null]);

        $this->artisan('statistics:snapshot')->assertSuccessful();
        Snapshots::capture();

        expect(StatisticsSnapshot::count())->toBe(2);
        $georgia = StatisticsSnapshot::where('state_id', $this->georgia->id)->first();
        expect($georgia->metrics['mills'])->toBe(2)
            ->and($georgia->metrics['fields']['email'])->toBe(2)
            ->and($georgia->metrics['types'])->toBe(['Sawmill' => 2]);
    });

    test('a state whose mills are all gone records zero instead of dropping out', function () {
        $mill = Mill::factory()->for($this->alabama)->create();
        Snapshots::capture(now()->subDay());
        $mill->delete();

        Snapshots::capture();

        expect(StatisticsSnapshot::whereDate('captured_on', today())->where('state_id', $this->alabama->id)->first()->metrics['mills'])->toBe(0);
    });

    test('opening a Statistics page records today\'s snapshot if nobody has', function () {
        Mill::factory()->for($this->georgia)->create();
        loginAdminUser(['statistics.see']);

        $this->get('/admin/statistics')->assertOk();
        $this->get('/admin/statistics')->assertOk();

        expect(StatisticsSnapshot::whereDate('captured_on', today())->count())->toBe(1);
    });

    test('the snapshot is scheduled daily', function () {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());

        expect($events->contains(fn ($event) => str_contains($event->command, 'statistics:snapshot')))->toBeTrue();
    });
});

describe('public submissions', function () {
    test('each submission is placed in its mill\'s state, or the state it was submitted with', function () {
        $mill = Mill::factory()->for($this->georgia)->create();
        MillEdit::factory()->for($mill)->withChanges(['email' => 'x@example.com'])->create();
        MillEdit::factory()->newBusiness(['state_id' => $this->alabama->id])->create();

        $georgia = SubmissionStats::for($this->georgia)->rows();
        $alabama = SubmissionStats::for($this->alabama)->rows();

        expect($georgia)->toHaveCount(1)
            ->and($georgia->first()->is_new)->toBeFalse()
            ->and($georgia->first()->fields)->toContain('email')
            ->and($alabama)->toHaveCount(1)
            ->and($alabama->first()->is_new)->toBeTrue();
    });

    test('pending submissions are grouped by how long they\'ve waited', function () {
        foreach ([1, 10, 40, 200] as $days) {
            MillEdit::factory()->newBusiness(['state_id' => $this->georgia->id])->create(['created_at' => now()->subDays($days)]);
        }

        expect(SubmissionStats::for($this->georgia)->pendingAges())->toBe([
            'Under a week' => 1,
            '1–4 weeks' => 1,
            '1–3 months' => 1,
            'Over 3 months' => 1,
        ]);
    });

    test('review time is the median of reviews in the last year', function () {
        foreach ([2, 4, 10] as $days) {
            MillEdit::factory()->newBusiness(['state_id' => $this->georgia->id])->create([
                'status' => PublicationStatus::Approved,
                'created_at' => now()->subDays(20),
                'reviewed_at' => now()->subDays(20 - $days),
            ]);
        }

        $times = SubmissionStats::for($this->georgia)->reviewTimes();

        expect($times['reviews'])->toBe(3)->and($times['median'])->toBe(4.0);
    });

    test('corrections count each field they change', function () {
        $mill = Mill::factory()->for($this->georgia)->create();
        MillEdit::factory()->for($mill)->withChanges(['email' => 'x@example.com', 'web_site' => 'https://x.example'])->create();
        MillEdit::factory()->for($mill)->withChanges(['email' => 'y@example.com'])->create();

        expect(SubmissionStats::for($this->georgia)->fieldsChanged())->toBe(['Email' => 2, 'Website' => 1]);
    });
});

describe('activity', function () {
    test('changes are counted by source, and a state only counts its own mills', function () {
        // fresh(): as an admin edit would, start from the stored row (with its column defaults)
        $georgia = Mill::factory()->for($this->georgia)->create()->fresh();
        $alabama = Mill::factory()->for($this->alabama)->create()->fresh();
        loginAdminUser(['mills.edit']);
        $georgia->update(['mill_name' => 'Admin Edit']);
        $alabama->update(['mill_name' => 'Elsewhere']);
        ActivitySource::as(ActivitySource::PUBLIC_SUBMISSION, fn () => $georgia->update(['mill_name' => 'Public Edit']));

        $month = now()->format('Y-m');
        $bySource = ActivityStats::for($this->georgia)->perMonthBySource();

        expect($bySource['Admin edits'][$month])->toBe(1)
            ->and($bySource['Approved public submissions'][$month])->toBe(1)
            ->and(ActivityStats::for($this->georgia)->millFieldsChanged())->toBe(['Mill name' => ['Admin edits' => 1, 'Approved public submissions' => 1]]);
    });

    test('mills edited counts people\'s changes, not imports or background jobs', function () {
        $mill = Mill::factory()->for($this->georgia)->create()->fresh();
        ActivitySource::as(ActivitySource::IMPORT, fn () => $mill->update(['mill_name' => 'Imported']));
        ActivitySource::as(ActivitySource::SYSTEM, fn () => $mill->update(['mill_name' => 'Geocoded']));

        expect(ActivityStats::for($this->georgia)->millsEditedSince(30))->toBe(0);

        loginAdminUser(['mills.edit']);
        $mill->update(['mill_name' => 'Edited']);

        expect(ActivityStats::for($this->georgia)->millsEditedSince(30))->toBe(1);
    });
});

describe('similar names', function () {
    /** Names from a group, for comparing without caring about order. */
    function groupNames(array $groups): array
    {
        return array_map(function ($g) {
            $names = array_column($g['names'], 'name');
            sort($names);

            return $names;
        }, $groups);
    }

    test('words ignore case, punctuation, order, plurals and "and"', function () {
        expect(SimilarNames::words('Pulp & Paper'))->toBe(SimilarNames::words('Pulp - Paper'))
            ->and(SimilarNames::words('Post Pole'))->toBe(SimilarNames::words('Pole and Post'))
            ->and(SimilarNames::words('Veneer / Plywood / Panels'))->toBe(['panel', 'plywood', 'veneer'])
            ->and(SimilarNames::words('Grass'))->toBe(['grass']);
    });

    test('likely-same groups catch rewording and abbreviations, but not typos', function () {
        foreach (['Pulp & Paper', 'Pulp Paper', 'Pulp - Paper', 'OSB', 'Oriented Strand Board', 'Firewood', 'Firewod', 'Pallet', 'Pellet', 'Sawmill'] as $name) {
            MillType::create(['name' => $name]);
        }

        $groups = SimilarNames::millTypes()->groups();

        expect(groupNames($groups))->toEqualCanonicalizing([
            ['Pulp & Paper', 'Pulp - Paper', 'Pulp Paper'],
            ['OSB', 'Oriented Strand Board'],
        ]);
        expect(collect($groups)->firstWhere(fn ($g) => in_array('OSB', array_column($g['names'], 'name')))['reasons'])
            ->toBe(['Abbreviation']);
    });

    test('names inside other names, and typos, are listed as possibly related, once per pair', function () {
        foreach (['Chip', 'Chip Mill', 'Pulp', 'Pulp & Paper', 'Pulp Paper', 'Mulch', 'Pallet', 'Pellet', 'Firewood', 'Firewod', 'Pole', 'Post'] as $name) {
            MillType::create(['name' => $name]);
        }

        $related = collect(SimilarNames::millTypes()->related())
            ->map(fn ($p) => [array_column($p['first'], 'name'), array_column($p['second'], 'name'), $p['reason']]);

        expect($related)->toHaveCount(4)
            ->and($related)->toContain([['Chip'], ['Chip Mill'], 'Contained'])
            ->and($related)->toContain([['Pallet'], ['Pellet'], 'Spelling'])
            ->and($related)->toContain([['Firewod'], ['Firewood'], 'Spelling']);
        $pulp = $related->first(fn ($p) => $p[0] === ['Pulp']);
        expect($pulp[1])->toEqualCanonicalizing(['Pulp & Paper', 'Pulp Paper'])
            ->and($pulp[2])->toBe('Contained');
    });

    test('each name counts its mills and states, and works for wood species too', function () {
        $hardwood = WoodSpecies::factory()->create(['name' => 'Hardwood']);
        $hardwoods = WoodSpecies::factory()->create(['name' => 'Hardwoods']);
        Mill::factory()->count(2)->for($this->georgia)->create()->each(fn ($m) => $m->woodSpecies()->attach($hardwood));
        Mill::factory()->for($this->alabama)->create()->woodSpecies()->attach($hardwood);

        $group = SimilarNames::woodSpecies()->groups()[0];

        expect($group['names'][0])->toMatchArray(['name' => 'Hardwood', 'mills' => 3, 'states' => 2])
            ->and($group['names'][1])->toMatchArray(['name' => 'Hardwoods', 'mills' => 0, 'states' => 0])
            ->and($group['mills'])->toBe(3);
    });

    test('mapper mentions come from string literals in the mapper code, not comments', function () {
        $strings = SimilarNames::mapperStrings();

        // Georgia's crosswalk outputs "Sawmill"; its "Pole - Post" mapping is commented out
        expect($strings['sawmill'] ?? [])->toContain('Georgia')
            ->and($strings['pole - post'] ?? [])->not->toContain('Georgia');
    });

    test('the page lists the groups and links names to their edit page for users who can edit', function () {
        $a = MillType::factory()->create(['name' => 'Pulp & Paper']);
        MillType::factory()->create(['name' => 'Pulp Paper']);
        loginAdminUser(['statistics.see', 'mill_types.edit']);

        $this->get('/admin/statistics/similar')->assertOk()
            ->assertSee('Pulp &amp; Paper, Pulp Paper', false)
            ->assertSee(backpack_url("mill-type/{$a->id}/edit"))
            ->assertSee('No wood species look like the same name written differently.');
    });

    test('without edit permission the names are plain text', function () {
        $a = MillType::factory()->create(['name' => 'Pulp & Paper']);
        MillType::factory()->create(['name' => 'Pulp Paper']);
        loginAdminUser(['statistics.see']);

        $this->get('/admin/statistics/similar')->assertOk()
            ->assertDontSee(backpack_url("mill-type/{$a->id}/edit"));
    });
});
