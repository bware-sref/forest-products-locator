<?php

/**
 * Not sure where to even begin testing this...
 * I suppose the first things would be
 * 0. seed the DB!
 * 1. tests
 * - it exits successfully
 * - it writes a file (probably need to delete or move any existing file first...nah, use a distinct file name for testing)
 * - it contains all the pages and mills we seeded (much easier if we don't seed :-D)
 */
pest()->group('sitemap');

it('exits successfully', function () {
    $this->artisan('zed:sitemap', ['test-sitemap.xml'])
        ->assertSuccessful();
});

/**
 * And the Rest!
 */