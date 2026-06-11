<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
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
        ]);
    }

    public function show(Faq $faq)
    {
        $faq->load(['faqCategory']);
        
    }
}
