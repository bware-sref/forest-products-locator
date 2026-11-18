<?php

use App\Models\Faq;
use Database\Seeders\FaqSeeder;

beforeEach(fn() => $this->seed(FaqSeeder::class));

test('it seeds every FAQ, in order', function () {
    // $this->seed(FaqSeeder::class);

    // expect(FaqCategory::pluck('name')->all())->toBe(array_keys(FaqCategorySeeder::FAQS))
    //     ->and(Faq::count())->toBe(collect(FaqCategorySeeder::FAQS)->flatten()->count());
    expect(Faq::pluck('question')->all())->toBe(array_keys(FaqSeeder::FAQS))
        ->and(Faq::count())->toBe(collect(FaqSeeder::FAQS)->count());

    /**
     * The following is redundant without categories.
     */
    // $help = FaqCategory::where('name', 'Help')->first();
    // expect($help->faqs()->pluck('question')->all())->toBe(array_keys(FaqCategorySeeder::FAQS['Help']));
});

test('answers are stored as HTML', function () {
    // $this->seed(FaqSeeder::class);

    $narrowing = Faq::where('question', 'How do I narrow down the results?')->first();
    expect($narrowing->answer)
        ->toContain('<strong>Filters</strong>', '<ul>', '<li>state and county</li>')
        ->not->toContain('**');

    expect(Faq::where('question', 'Do you collect any information about visitors?')->first()->answer)
        ->toContain('<a href="https://tools.google.com/dlpage/gaoptout">');
});

test('running it again updates the FAQs instead of adding copies, and leaves other FAQs alone', function () {
    // $this->seed(FaqCategorySeeder::class);
    $count = Faq::count();
    Faq::where('question', 'How do I contact you?')->update(['answer' => 'edited']);
    $other = Faq::factory()->create();

    // $this->seed(FaqCategorySeeder::class);
    $this->seed(FaqSeeder::class);

    expect(Faq::count())->toBe($count + 1)
        // ->and(FaqCategory::where('name', 'Help')->count())->toBe(1)
        ->and(Faq::where('question', 'How do I contact you?')->first()->answer)->toContain('Contact page')
        ->and($other->fresh())->not->toBeNull();
});
