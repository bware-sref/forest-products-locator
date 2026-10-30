<?php

use App\Models\Faq;
use App\Models\FaqCategory;
use Inertia\Testing\AssertableInertia as Assert;

test('FAQs are grouped by category, in order', function () {
    $second = FaqCategory::factory()->create(['name' => 'Second', 'order' => 2]);
    $first = FaqCategory::factory()->create(['name' => 'First', 'order' => 1]);
    Faq::factory()->for($first)->create(['question' => 'Later question?', 'order' => 2, 'published_at' => now()->subDay(), 'unpublished_at' => null]);
    Faq::factory()->for($first)->create(['question' => 'Earlier question?', 'order' => 1, 'published_at' => now()->subDay(), 'unpublished_at' => null]);
    Faq::factory()->for($second)->create(['published_at' => now()->subDay(), 'unpublished_at' => null]);

    $this->get('/faqs')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faqs')
            ->where('faqsByCategory.0.name', 'First')
            ->where('faqsByCategory.1.name', 'Second')
            ->where('faqsByCategory.0.faqs.0.question', 'Earlier question?')
            ->where('faqsByCategory.0.faqs.1.question', 'Later question?')
        );
});

test('only currently published FAQs are shown', function (array $dates) {
    $category = FaqCategory::factory()->create();
    Faq::factory()->for($category)->create(['question' => 'Visible?', 'published_at' => now()->subDay(), 'unpublished_at' => null]);
    Faq::factory()->for($category)->create(['question' => 'Hidden?', ...$dates]);

    $this->get('/faqs')
        ->assertInertia(fn (Assert $page) => $page
            ->has('faqsByCategory.0.faqs', 1)
            ->where('faqsByCategory.0.faqs.0.question', 'Visible?')
        );
})->with([
    'not published yet' => [['published_at' => now()->addDay(), 'unpublished_at' => null]],
    'unpublished' => [['published_at' => now()->subWeek(), 'unpublished_at' => now()->subDay()]],
]);
