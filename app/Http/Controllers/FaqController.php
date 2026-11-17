<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\PageSeo;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * The name is no longer possibly misleading.
     * We no longer query all FaqCategories with their babies.
     */
    public function index()
    {
        $faqs = Faq::query()
            ->orderBy(column: 'order', direction: 'asc')
            ->get();

        return Inertia::render('faqs', [
            'faqs' => $faqs,
            'pageTitle' => 'Frequently Asked Questions',
            'pageSeo' => PageSeo::resolve(
                'faqs',
                'Frequently Asked Questions',
                'Answers to frequently asked questions about the Forest Products Locator.'
            ),
        ]);
    }

    /**
     * I think this was intended to allow linking directly to individual
     * FAQs.
     * @param Faq $faq
     * @return void
     */
    public function show(Faq $faq)
    {
        $faq->load(['faqCategory']);

    }
}
