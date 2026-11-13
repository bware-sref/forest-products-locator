<?php

use App\Models\Faq;
use Database\Seeders\FaqCategorySeeder;

/**
 * The search box on the FAQ page: lists the FAQs matching every word typed (in the question or the answer), and
 * jumps to the one picked.
 */

/** Content fades in on load; see A11yTest. */
const FAQ_FADE_IN_SECONDS = 2;

beforeEach(function () {
    $this->seed(FaqCategorySeeder::class);
});

function faqId(string $question): string
{
    return 'faq-'.Faq::where('question', $question)->value('id');
}

test('typing lists the FAQs whose question or answer has every word', function () {
    visit('/faqs')
        ->wait(FAQ_FADE_IN_SECONDS)
        // both words are only in these answers ("Reset Location"; "resets everything" and "a location")
        ->typeSlowly('#faq-search', 'reset location', 20)
        ->wait(1)
        ->assertSeeIn('[role=listbox]', 'Can I find mills near a specific location?')
        ->assertSeeIn('[role=listbox]', 'How do I narrow down the results?')
        ->assertDontSeeIn('[role=listbox]', 'Do you save my location?')
        // announced to screen readers (visually hidden)
        ->assertSourceHas('2 questions found.');
});

test('picking a result scrolls to that FAQ and moves focus to it, keeping the search', function () {
    $page = visit('/faqs')
        ->wait(FAQ_FADE_IN_SECONDS)
        ->typeSlowly('#faq-search', 'cookies', 20)
        ->wait(1)
        ->click('[role=option]')
        ->wait(1)
        ->assertValue('#faq-search', 'cookies');

    expect($page->script('document.activeElement.id'))->toBe(faqId('Does the site use cookies?'))
        ->and($page->script('document.activeElement.getBoundingClientRect().top < window.innerHeight'))->toBeTrue();
});

test('a result can be picked with the keyboard', function () {
    $page = visit('/faqs')
        ->wait(FAQ_FADE_IN_SECONDS)
        ->typeSlowly('#faq-search', 'export', 20)
        ->wait(1)
        ->keys('#faq-search', ['ArrowDown', 'Enter'])
        ->wait(1);

    expect($page->script('document.activeElement.id'))->toBe(faqId('Can I download the results?'));
});

test('a search with no matches says so', function () {
    visit('/faqs')
        ->wait(FAQ_FADE_IN_SECONDS)
        ->typeSlowly('#faq-search', 'zebra', 20)
        ->wait(1)
        ->assertSee('No questions match “zebra”.');
});

test('the open results have no accessibility issues', function () {
    $page = visit('/faqs')
        ->wait(FAQ_FADE_IN_SECONDS)
        ->assertNoAccessibilityIssues(a11yLevel())
        ->typeSlowly('#faq-search', 'mill', 20)
        ->wait(1);

    /*
     * While the results are open, Base UI hides the rest of the page from screen readers (aria-hidden) so they stay
     * in the search, and axe objects that the hidden part still has links in it. Tabbing out of the search closes
     * the results and unhides the page before focus gets there, so that rule is skipped here, but only here.
     * (At A11Y_LEVEL 2 and up, the hiding also brings moderate "no visible h1" and "list outside a landmark" issues.)
     */
    $violations = $page->script(<<<'JS'
        async () => (await window.axe.run({ rules: { 'aria-hidden-focus': { enabled: false } } }))
            .violations.map((violation) => ({ impact: violation.impact, rule: violation.id }))
    JS);
    // the same levels assertNoAccessibilityIssues() uses
    $reported = array_slice(['critical', 'serious', 'moderate', 'minor'], 0, a11yLevel() + 1);

    expect(array_filter($violations, fn (array $violation) => in_array($violation['impact'], $reported, true)))->toBe([]);
})->group('a11y');
