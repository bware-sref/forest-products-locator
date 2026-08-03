<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\PageSeo;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * The name is possibly misleading.
     * We query all FaqCategories with their babies.
     */
    public function index()
    {
        $faqsByCategory = FaqCategory::query()
            ->orderBy(column: 'order', direction: 'asc')
            ->with('faqs')
            ->get();

        return Inertia::render('faqs', [
            'faqsByCategory' => $faqsByCategory,
            'pageTitle' => 'FAQs',
            'pageSeo' => PageSeo::resolve(
                'faqs',
                'FAQs',
                'Answers to frequently asked questions about the Forest Products Locator.'
            ),
        ]);
    }

    public function show(Faq $faq)
    {
        $faq->load(['faqCategory']);

    }
}
