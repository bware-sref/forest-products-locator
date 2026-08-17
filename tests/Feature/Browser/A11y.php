<?php

$siteUrls = [
    '/',
    '/mill-map',
    '/mill-list',
    '/states', // states has 13 child pages
    '/add-business',
    '/faqs',
    '/contact',
    '/about-us',
];

test('it has no a11y issues on desktop', function () use($siteUrls) {
    $pages = visit($siteUrls);

    /**
     * wait for 2 seconds before evaluating for a11y issues because content fades in.
     * if testing happens sooner, it gets a blended color version that has insufficient contrast!
     */
    foreach ($pages as $page) {
        $page->wait(2)
            ->assertNoAccessibilityIssues();
    }    
});

test('it has no a11y issues on mobile', function () use($siteUrls) {
    $pages = visit($siteUrls)
        ->on()
        ->mobile();

    /**
     * wait for 2 seconds before evaluating for a11y issues because content fades in.
     * if testing happens sooner, it gets a blended color version that has insufficient contrast!
     */
    foreach ($pages as $page) {
        $page->wait(2)
            ->assertNoAccessibilityIssues();
    }    
});