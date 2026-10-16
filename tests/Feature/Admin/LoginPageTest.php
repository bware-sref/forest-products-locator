<?php
// words

test('it can visit the login page', function () {
    $page = visit('/admin/login');

    $page->wait(2)
        ->screenshot(
            fullPage: true,
            filename: date('Y-m-d__') . 'it-can-visit-login'
        );

    $page->assertSee('Login')
        ->assertNoSmoke();

})->group('bob');