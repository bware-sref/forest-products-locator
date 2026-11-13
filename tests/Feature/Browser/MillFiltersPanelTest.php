<?php

use App\Models\County;
use App\Models\Mill;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;

/**
 * The mill filters panel on Mill List and Mill Map.
 * - Desktop: an in-page panel, open by default, with the active filters shown as removable chips in the title bar.
 * - Mobile: a modal Sheet, closed by default, with a count of active filters on the Filters button.
 * - Filter selections and the desktop panel's open/closed state last for the browser session (sessionStorage),
 *   so they carry between Mill List and Mill Map.
 */

/** Content fades in on load; see A11yTest. */
const FILTERS_FADE_IN_SECONDS = 2;

beforeEach(function () {
    $this->georgia = namedState('Georgia', 'GA');
    $this->sawmill = MillType::factory()->create();
    $this->hardwood = WoodSpecies::factory()->create();
    $this->clarke = County::factory()->for($this->georgia)->create(['name' => 'Clarke Testing']);

    $acme = Mill::factory()->for($this->georgia)->create(['mill_name' => 'Acme Lumber', 'county_id' => $this->clarke->id]);
    $acme->millTypes()->attach($this->sawmill);
    $acme->woodSpecies()->attach($this->hardwood);
    Mill::factory()->for($this->georgia)->count(2)->create();
    Mill::factory()->for(State::factory()->create())->count(2)->create();
});

/** Saves filters the way the page does, so the next page load restores them. */
function saveFilters(array $searchParams, string $searchText = ''): string
{
    $saved = json_encode([
        'searchText' => $searchText,
        'searchParams' => $searchParams,
        'coordinates' => null,
        'radius' => null,
        'locationLabel' => null,
    ]);

    return "sessionStorage.setItem('fpn:mill-filters', JSON.stringify({$saved}))";
}

/** Headings a screen reader can reach: not hidden, and not inside something aria-hidden. */
const EXPOSED_HEADINGS_JS = <<<'JS'
    [...document.querySelectorAll('h1, h2, h3, h4, h5, h6')]
        .filter((el) => !el.closest('[aria-hidden=true], [hidden]'))
        .map((el) => `${el.tagName} ${el.textContent.trim()}`)
JS;

describe('desktop', function () {
    test('screen readers hear the page title once, and a "Filters" heading for the panel', function (string $path, string $title) {
        $headings = visit($path)->wait(FILTERS_FADE_IN_SECONDS)->script(EXPOSED_HEADINGS_JS);

        expect($headings)->toContain("H1 {$title}", 'H2 Filters')
            ->and(array_filter($headings, fn ($heading) => str_ends_with($heading, " {$title}")))->toHaveCount(1);
    })->with([
        'mill list' => ['/mill-list', 'Mill List'],
        'mill map' => ['/mill-map', 'Mill Map'],
    ]);

    test('the panel starts open, and the Filters button closes it', function (string $path) {
        visit($path)
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertVisible('#mill-filters-panel')
            ->assertAttribute('#filter-trigger', 'aria-expanded', 'true')
            ->assertAttribute('#filter-trigger', 'aria-controls', 'mill-filters-panel')
            ->click('#filter-trigger')
            ->assertMissing('#mill-filters-panel')
            ->assertAttribute('#filter-trigger', 'aria-expanded', 'false');
    })->with(['mill list' => '/mill-list', 'mill map' => '/mill-map']);

    test('a closed panel stays closed when moving to the other page', function () {
        visit('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->click('#filter-trigger')
            ->navigate('/mill-map')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertMissing('#mill-filters-panel')
            ->assertAttribute('#filter-trigger', 'aria-expanded', 'false');
    });

    test('the panel has no Show Results button, since the results are already visible', function () {
        visit('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertDontSee('Show Results');
    });

    test('a search carries over to the other page, with its chip', function () {
        visit('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->type('#q', 'Acme')
            // text search is debounced
            ->wait(2)
            ->assertSourceHas('<span role="status" class="sr-only">1 mill found.</span>')
            ->navigate('/mill-map')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertValue('#q', 'Acme')
            ->assertSeeIn('#active-filter-q', 'Name contains "Acme"')
            ->assertSourceHas('<span role="status" class="sr-only">1 mill found.</span>');
    });

    test('saved filters are restored as chips named for what was picked', function () {
        $page = visit('/mill-list')->wait(FILTERS_FADE_IN_SECONDS);
        $page->script(saveFilters(['state' => (string) $this->georgia->id, 'millType' => (string) $this->sawmill->id]));

        $page->navigate('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertSeeIn('#active-filter-state', 'Georgia')
            ->assertSeeIn('#active-filter-millType', $this->sawmill->name)
            ->assertSourceHas('<span role="status" class="sr-only">1 mill found.</span>');
    });

    test('removing a chip drops that filter and moves focus to the next chip, then to the Filters button', function () {
        $page = visit('/mill-list')->wait(FILTERS_FADE_IN_SECONDS);
        $page->script(saveFilters(['state' => (string) $this->georgia->id, 'millType' => (string) $this->sawmill->id]));
        $page->navigate('/mill-list')->wait(FILTERS_FADE_IN_SECONDS);

        $page->click('#active-filter-state')
            ->wait(2)
            ->assertNotPresent('#active-filter-state')
            // only the mill type is left: the one sawmill
            ->assertSourceHas('<span role="status" class="sr-only">1 mill found.</span>');
        expect($page->script('document.activeElement.id'))->toBe('active-filter-millType');

        $page->click('#active-filter-millType')
            ->wait(2)
            ->assertNotPresent('#active-filter-millType')
            ->assertSourceHas('<span role="status" class="sr-only">5 mills found.</span>');
        expect($page->script('document.activeElement.id'))->toBe('filter-trigger');
    });

    test('picking from the dropdowns shows what was picked on the chips, and it carries over', function () {
        // the server sends these options' values as numbers; they have to match the chips' string ids
        $page = visit('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->click('#state')->click('Georgia')->wait(1)
            ->click('#county')->click('Clarke Testing')->wait(1)
            ->click('#millType')->click($this->sawmill->name)->wait(1)
            ->click('#woodSpecies')->click($this->hardwood->name)
            ->wait(2)
            ->assertSeeIn('#active-filter-state', 'Georgia')
            ->assertSeeIn('#active-filter-county', 'Clarke Testing')
            ->assertSeeIn('#active-filter-millType', $this->sawmill->name)
            ->assertSeeIn('#active-filter-woodSpecies', $this->hardwood->name)
            ->assertSourceHas('<span role="status" class="sr-only">1 mill found.</span>');

        $page->navigate('/mill-map')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertSeeIn('#active-filter-county', 'Clarke Testing')
            ->assertSeeIn('#active-filter-millType', $this->sawmill->name)
            ->assertSeeIn('#active-filter-woodSpecies', $this->hardwood->name)
            // the selects show the restored choices too
            ->assertSeeIn('#millType', $this->sawmill->name)
            ->assertSeeIn('#woodSpecies', $this->hardwood->name)
            ->assertSourceHas('<span role="status" class="sr-only">1 mill found.</span>');
    });

    test('the panel\'s close button closes it and returns focus to the Filters button', function (string $path) {
        $page = visit($path)
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->click('#mill-filters-close')
            ->assertMissing('#mill-filters-panel')
            ->assertAttribute('#filter-trigger', 'aria-expanded', 'false');

        expect($page->script('document.activeElement.id'))->toBe('filter-trigger');
    })->with(['mill list' => '/mill-list', 'mill map' => '/mill-map']);

    test('removing the search chip clears the search box too', function () {
        visit('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->type('#q', 'Acme')
            ->wait(2)
            ->click('#active-filter-q')
            ->wait(2)
            ->assertValue('#q', '')
            ->assertSourceHas('<span role="status" class="sr-only">5 mills found.</span>');
    });

    test('the open panel has no accessibility issues', function (string $path) {
        $page = visit($path)->wait(FILTERS_FADE_IN_SECONDS);
        $page->script(saveFilters(['state' => (string) $this->georgia->id], 'Acme'));

        $page->navigate($path)
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertVisible('#mill-filters-panel')
            ->assertPresent('#active-filter-state')
            ->assertNoAccessibilityIssues(a11yLevel());
    })->with(['mill list' => '/mill-list', 'mill map' => '/mill-map'])->group('a11y');
});

describe('mobile', function () {
    test('the filters start closed, and there are no chips', function (string $path) {
        $page = visit($path)->on()->mobile()->wait(FILTERS_FADE_IN_SECONDS);
        $page->script(saveFilters(['state' => (string) $this->georgia->id]));

        $page->navigate($path)
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertAttribute('#filter-trigger', 'aria-expanded', 'false')
            ->assertMissing('#q')
            ->assertMissing('#active-filter-state');
    })->with(['mill list' => '/mill-list', 'mill map' => '/mill-map']);

    test('the Filters button says how many filters are on', function () {
        $page = visit('/mill-list')->on()->mobile()->wait(FILTERS_FADE_IN_SECONDS);
        $page->script(saveFilters(['state' => (string) $this->georgia->id, 'millType' => (string) $this->sawmill->id]));

        $page->navigate('/mill-list')
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->assertSeeIn('#filter-trigger [aria-hidden=true]', '2')
            ->assertSourceHas(', 2 active');
    });

    test('the open filters keep the page title as their heading', function () {
        // the Sheet hides the title bar from screen readers, so the title isn't repeated
        $headings = visit('/mill-map')
            ->on()->mobile()
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->click('#filter-trigger')
            ->wait(1)
            ->script(EXPOSED_HEADINGS_JS);

        expect($headings)->toContain('H2 Mill Map')->not->toContain('H2 Filters');
    });

    test('Show Results closes the filters', function () {
        visit('/mill-list')
            ->on()->mobile()
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->click('#filter-trigger')
            ->wait(1)
            ->assertVisible('#q')
            ->click('Show Results')
            ->wait(1)
            ->assertMissing('#q')
            ->assertAttribute('#filter-trigger', 'aria-expanded', 'false');
    });

    test('the open filters have no accessibility issues', function () {
        visit('/mill-map')
            ->on()->mobile()
            ->wait(FILTERS_FADE_IN_SECONDS)
            ->click('#filter-trigger')
            ->wait(1)
            ->assertNoAccessibilityIssues(a11yLevel());
    })->group('a11y');
});
