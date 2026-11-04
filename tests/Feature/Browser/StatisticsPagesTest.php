<?php

use App\Enums\PublicationStatus;
use App\Enums\UserRoles;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\State;
use App\Models\User;
use App\Models\WoodSpecies;
use App\Statistics\Snapshots;
use App\Support\ActivitySource;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The Statistics pages in a real browser: every chart draws (Chart.js), the county map draws (Leaflet), nothing
 * errors, in both themes. Screenshots land in tests/Browser/Screenshots/ for a visual check.
 * Needs the network: Basset fetches Chart.js, Leaflet and the county boundaries from jsDelivr on first use.
 */

beforeEach(function () {
    $georgia = State::factory()->create(['name' => 'Georgia', 'abbreviation' => 'GA']);
    $alabama = State::factory()->create(['name' => 'Alabama', 'abbreviation' => 'AL']);

    // a few real Georgia counties (FIPS 13xxx), so the map has shapes to shade
    $counties = collect(['Fulton' => '121', 'Cobb' => '067', 'DeKalb' => '089', 'Gwinnett' => '135', 'Appling' => '001', 'Bibb' => '021'])
        ->map(fn ($fips, $name) => County::forceCreate(['name' => $name, 'state_id' => $georgia->id, 'state_code' => '13', 'fips_code' => $fips]))
        ->values();

    $types = collect(['Sawmill', 'Pallet', 'Pulp & Paper', 'Chip Mill', 'Pellet', 'Other'])->map(fn ($name) => MillType::factory()->create(['name' => $name]));
    $species = collect(['Pine', 'Oak', 'Poplar'])->map(fn ($name) => WoodSpecies::factory()->create(['name' => $name]));

    foreach ([$georgia, $alabama] as $state) {
        Mill::factory()->for($state)->count($state->is($georgia) ? 30 : 12)->create()->each(function (Mill $mill, int $i) use ($types, $species, $counties, $state) {
            $mill->millTypes()->attach($types->random(random_int(1, 2)));
            $mill->woodSpecies()->attach($species->random(random_int(1, 3)));
            DB::table('mills')->where('id', $mill->id)->update([
                'county_id' => $state->name === 'Georgia' ? $counties[$i % 4]->id : null,
                'size' => ['Small', 'Medium', 'Large', ''][$i % 4],
                'email' => $i % 3 ? $mill->email : null,
                'created_at' => now()->subMonths($i % 14),
                'updated_at' => now()->subDays($i * 17),
            ]);
        });
    }

    $mill = Mill::where('state_id', $georgia->id)->first();
    MillEdit::factory()->for($mill)->withChanges(['email' => 'new@example.com', 'web_site' => 'https://new.example'])->create(['created_at' => now()->subDays(12)]);
    MillEdit::factory()->newBusiness(['state_id' => $georgia->id])->create(['created_at' => now()->subDays(50)]);
    MillEdit::factory()->newBusiness(['state_id' => $alabama->id])->create(['status' => PublicationStatus::Approved, 'created_at' => now()->subDays(40), 'reviewed_at' => now()->subDays(37)]);

    $user = User::factory()->create(['name' => 'Stats Tester']);
    foreach (['statistics.see', 'statistics.compare_states', 'mills.edit', 'activity_log.see'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $user->assignRole(Role::findOrCreate(UserRoles::SUPER->value, 'web'));
    // see UploadFieldContrastTest for why not actingAs($user, 'backpack')
    backpack_auth()->setUser($user);

    ActivitySource::as(ActivitySource::ADMIN, fn () => $mill->fresh()->update(['mill_name' => 'Edited In Admin']));
    ActivitySource::as(ActivitySource::PUBLIC_SUBMISSION, fn () => $mill->fresh()->update(['telephone' => '404-555-0100']));

    Snapshots::capture(now()->subDays(40));
    Snapshots::capture(now()->subDays(10));
});

$pages = [
    'overview' => '/admin/statistics',
    'state' => '/admin/statistics/state/GA',
    'submissions' => '/admin/statistics/submissions',
    'activity' => '/admin/statistics/activity',
    'compare' => '/admin/statistics/compare',
];

foreach (['light', 'dark'] as $theme) {
    foreach ($pages as $name => $path) {
        test("the {$name} statistics page draws its charts in {$theme} mode", function () use ($theme, $name, $path) {
            $page = visit($path);
            $page->script("document.documentElement.setAttribute('data-bs-theme', '{$theme}')");
            $page->wait(2);

            $page->assertNoSmoke();

            $expected = $page->script("document.querySelectorAll('[data-stats-chart]').length");
            $drawn = $page->script("Object.keys(Chart.instances).length");
            expect($expected)->toBeGreaterThan(0)
                ->and($drawn)->toBe($expected);

            if ('state' === $name) {
                expect($page->script("document.querySelectorAll('.stats-map path.leaflet-interactive').length"))->toBeGreaterThan(0);
            }

            $page->screenshot(fullPage: true, filename: date('Y-m-d__')."statistics-{$name}-{$theme}");
        })->group('browser', 'statistics', 'network');
    }
}
