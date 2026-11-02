<?php

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\WoodSpecies;

/**
 * Automated accessibility checks (axe-core, via Pest's assertNoAccessibilityIssues()) on every public page.
 *
 * axe only judges the page as it stands when it runs, so states reached by interacting (menus open, forms with
 * errors) get their own tests below. It finds roughly a third of WCAG failures; the manual test plan covers the rest.
 *
 * Severity: 0 = critical only, 1 = critical + serious (the default, and the bar these tests hold), 3 = everything.
 * For an audit run that reports every issue, set A11Y_LEVEL inside the container (`A11Y_LEVEL=3 sail test` doesn't
 * work: Sail doesn't pass host environment variables through):
 *   sail bash -c 'A11Y_LEVEL=3 php artisan test --group=a11y'
 */
function a11yLevel(): int
{
    return (int) (getenv('A11Y_LEVEL') ?: 1);
}

/**
 * Content fades in on load; checking sooner measures a half-faded color and reports contrast issues that aren't real.
 */
const A11Y_FADE_IN_SECONDS = 2;

beforeEach(function () {
    $this->state = fullyBuiltState();

    $types = MillType::factory()->count(3)->create();
    $species = WoodSpecies::factory()->count(3)->create();

    $this->mill = Mill::factory()->for($this->state)->create();
    $this->mill->millTypes()->attach($types->pluck('id'));
    $this->mill->woodSpecies()->attach($species->pluck('id'));
    Mill::factory()->for($this->state)->count(4)->create();

    $category = FaqCategory::factory()->create();
    Faq::factory()->for($category)->count(3)->create(['published_at' => now()->subDay(), 'unpublished_at' => null]);

    $this->edit = MillEdit::factory()->for($this->mill)->withChanges(['mill_name' => 'Renamed Lumber Co'])->create();
    $this->newBusiness = MillEdit::factory()->newBusiness(['state_id' => $this->state->id])->create();
});

/**
 * Closures, because the URLs depend on records made in beforeEach.
 */
$pages = [
    'home' => fn () => '/',
    'mill map' => fn () => '/mill-map',
    'mill list' => fn () => '/mill-list',
    'single mill' => fn () => route('mills.show', test()->mill, false),
    'add a business' => fn () => '/add-business',
    'edit a mill' => fn () => '/mills/'.test()->mill->match_id.'/edit',
    'review an edit' => fn () => '/mill-edits/'.test()->edit->review_hash,
    'review a new business' => fn () => '/mill-edits/'.test()->newBusiness->review_hash,
    'faqs' => fn () => '/faqs',
    'about us' => fn () => '/about-us',
    'states' => fn () => '/states',
    'state page' => fn () => '/states/'.test()->state->slug,
    'contact' => fn () => '/contact',
    'not found' => fn () => '/no-such-page',
];

test('pages have no accessibility issues on desktop', function (Closure $url) {
    visit($url())
        ->wait(A11Y_FADE_IN_SECONDS)
        ->assertNoAccessibilityIssues(a11yLevel());
})->with($pages)->group('a11y', 'desktop');

test('pages have no accessibility issues on mobile', function (Closure $url) {
    visit($url())
        ->on()->mobile()
        ->wait(A11Y_FADE_IN_SECONDS)
        ->assertNoAccessibilityIssues(a11yLevel());
})->with($pages)->group('a11y', 'mobile');

describe('interactive states', function () {
    test('the open mobile menu', function () {
        visit('/')
            ->on()->mobile()
            ->wait(A11Y_FADE_IN_SECONDS)
            ->click('#mobile-menu-trigger')
            ->wait(1)
            ->assertVisible('#actual-ish-mobile-nav')
            ->assertNoAccessibilityIssues(a11yLevel());
    })->group('a11y', 'mobile');

    test('the mill form after submitting it empty', function () {
        visit('/add-business')
            ->wait(A11Y_FADE_IN_SECONDS)
            ->click('button[type=submit][form=form-submit-mill]')
            ->wait(1)
            ->assertNoAccessibilityIssues(a11yLevel());
    })->group('a11y', 'forms');

    test('the contact form after submitting it empty', function () {
        visit('/contact')
            ->wait(A11Y_FADE_IN_SECONDS)
            ->click('button[type=submit][form=form-contact]')
            ->wait(1)
            ->assertNoAccessibilityIssues(a11yLevel());
    })->group('a11y', 'forms');

    test('the mill list after a search that matches nothing', function () {
        visit('/mill-list')
            ->wait(A11Y_FADE_IN_SECONDS)
            // the first load announces its count
            ->assertSourceHas('<span role="status" class="sr-only">5 mills found.</span>')
            ->click('#filter-trigger')
            ->type('#q', 'zzzz-no-such-mill')
            ->keys('#q', 'Escape')
            // text search is debounced
            ->wait(2)
            ->assertSee('No mills match these filters.')
            ->assertSourceHas('<span role="status" class="sr-only">0 mills found.</span>')
            ->assertNoAccessibilityIssues(a11yLevel());
    })->group('a11y', 'forms');

    test('the mill map after a search that matches nothing', function () {
        visit('/mill-map')
            ->wait(A11Y_FADE_IN_SECONDS)
            ->click('#filter-trigger')
            ->type('#q', 'zzzz-no-such-mill')
            ->keys('#q', 'Escape')
            ->wait(2)
            ->assertSee('No mills match these filters.')
            ->assertSourceHas('<span role="status" class="sr-only">0 mills found.</span>')
            ->assertNoAccessibilityIssues(a11yLevel());
    })->group('a11y', 'forms');
});

test('clearing filters from the no-results message moves focus to the page heading', function () {
    // the button disappears once results load; without this, focus drops to the top of the page
    $page = visit('/mill-list')
        ->wait(A11Y_FADE_IN_SECONDS)
        ->click('#filter-trigger')
        ->type('#q', 'zzzz-no-such-mill')
        ->keys('#q', 'Escape')
        ->wait(2)
        ->click('Clear filters')
        ->wait(2)
        ->assertSourceHas('<span role="status" class="sr-only">5 mills found.</span>');

    expect($page->script('document.activeElement.id'))->toBe('mill-results-heading');
})->group('a11y', 'forms');

/**
 * Status messages a screen reader can actually hear: role="status" regions not hidden behind an open dialog.
 */
const EXPOSED_STATUS_TEXT_JS = <<<'JS'
    [...document.querySelectorAll('[role=status]')]
        .filter((el) => !el.closest('[aria-hidden=true]'))
        .map((el) => el.textContent)
JS;

test('search results are announced while the filters dialog is still open', function (string $path) {
    // the open dialog hides the title bar from screen readers, so its live region can't be the only one
    $page = visit($path)
        ->wait(A11Y_FADE_IN_SECONDS)
        ->click('#filter-trigger')
        ->type('#q', 'zzzz-no-such-mill')
        ->wait(2);

    expect($page->script(EXPOSED_STATUS_TEXT_JS))->toBe(['0 mills found.']);

    $page->keys('#q', 'Escape')->wait(1);

    expect($page->script(EXPOSED_STATUS_TEXT_JS))->toBe(['0 mills found.']);
})->with(['mill list' => '/mill-list', 'mill map' => '/mill-map'])->group('a11y', 'forms');
