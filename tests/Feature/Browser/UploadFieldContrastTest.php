<?php

use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Backpack's upload field shows a newly picked file's name in .backstrap-file-label, whose text color the field
 * hardcodes (#5c6873). theme-tabler swaps that label's background in dark mode, so the name was ~1.2:1.
 * (A saved record's file shows in .existing-file as a link instead, which was already fine.)
 */

const UPLOAD_LABEL_CONTRAST_JS = <<<'JS'
    (() => {
        const label = document.querySelector('[bp-field-name=icon] .backstrap-file-label');
        // resolve any color syntax (oklch, var(), etc.) to sRGB through a canvas
        const ctx = document.createElement('canvas').getContext('2d');
        const rgb = color => { ctx.fillStyle = '#000'; ctx.fillStyle = color; ctx.fillRect(0, 0, 1, 1); return [...ctx.getImageData(0, 0, 1, 1).data].slice(0, 3); };
        const lum = c => { const [r, g, b] = c.map(v => { v /= 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
        const style = getComputedStyle(label);
        const [a, b] = [lum(rgb(style.color)), lum(rgb(style.backgroundColor))];
        return { text: label.textContent.trim(), ratio: (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05) };
    })()
    JS;

beforeEach(function () {
    State::forceCreate(['name' => 'Alabama', 'abbreviation' => 'AL']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('state_forest_types.edit', 'web'));
    /**
     * Not actingAs($user, 'backpack'): it also makes backpack the *default* guard (auth()->shouldUse()), which a
     * real login doesn't, and Spatie resolves permissions against the default guard, so every 'web' permission
     * fails. Setting the user on the backpack guard alone is what a real admin login amounts to.
     */
    backpack_auth()->setUser($user);
});

foreach (['light', 'dark'] as $theme) {
    test("a newly picked upload file name meets AA contrast in {$theme} mode", function () use ($theme) {
        $page = visit('/admin/state-forest-type/create');

        $page->assertNoSmoke()
            ->script("document.documentElement.setAttribute('data-bs-theme', '{$theme}')");

        /**
         * attach() needs a local browser; Sail's Playwright isn't one ("localPaths are not allowed").
         * Pick the file the way a user would, as far as the page can tell: set input.files and fire change.
         */
        $page->script(<<<'JS'
            (() => {
                const input = document.getElementById('icon');
                const dt = new DataTransfer();
                dt.items.add(new File(['<svg xmlns="http://www.w3.org/2000/svg"/>'], 'favicon.svg', { type: 'image/svg+xml' }));
                input.files = dt.files;
                $(input).trigger('change');
            })()
        JS);

        $page->wait(1)
            ->screenshot(filename: date('Y-m-d__')."upload-field-label-{$theme}");

        $label = $page->script(UPLOAD_LABEL_CONTRAST_JS);

        expect($label['text'])->toBe('favicon.svg')
            ->and($label['ratio'])->toBeGreaterThanOrEqual(4.5);
    })->group('a11y', 'admin');
}
