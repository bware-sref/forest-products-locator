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
});
