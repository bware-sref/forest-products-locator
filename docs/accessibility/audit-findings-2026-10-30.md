# Forest Products Network — Accessibility Findings and Fixes

2026-10-30 · Bob Ware

This is a record of the automated audit as of 2026-10-30. The fixes are being applied in git, so check the a11y tests for the current state (see [How to verify a fix](#how-to-verify-a-fix)).

## Summary

The automated audit found 7 issues across all 14 public pages. Most are small, local fixes. Fixing the three color tokens and the review-page table styles clears most contrast failures, which are the biggest group.

The audit ran axe-core 4.10 at desktop and phone widths, plus three interactive states: the mobile menu open and both forms after an empty submit. It covers only what a machine can detect. The [manual test plan](manual-test-plan.md) covers the rest.

| # | Issue | Severity | Pages affected | Effort |
| --- | --- | --- | --- | --- |
| 1 | Unnamed remove buttons in the Mill types and Wood species pickers | Critical | Edit a mill | Small: one component |
| 2 | Low color contrast | Serious | Add a business, Edit a mill, Contact, State page, Review pages, Not found, FAQs (phone) | Small: 3 tokens plus classes on 3 pages |
| 3 | Map markers have no accessible name | Serious | Mill map, Single mill | Small: one prop |
| 4 | Sideways-scrolling table can't be reached by keyboard | Serious | Review pages (phone) | Small: three attributes |
| 5 | Header and footer aren't marked as landmarks, so content sits outside them | Moderate | All pages | Small: two tags |
| 6 | Header and footer menus share the label “Main” | Moderate | All pages (desktop) | Small: one prop |
| 7 | A second main area nested inside the page’s main area | Moderate | Review pages | Small: one tag per page |

**Suggested order:** 1, 3 and 4 first, because they block or hide content from keyboard and screen-reader users. Then 2, then 5–7 together, since they touch the same layout files.

## 1. Unnamed remove buttons in the pickers

**Severity:** critical. **Where:** the Mill types and Wood species pickers on Edit a mill. Add a business shows the same problem as soon as someone picks an item. The audit missed it there only because nothing was picked yet.

**Problem:** each picked item shows as a chip with a small × button to remove it. The button contains only an icon, so a screen reader announces just “button”, with no hint of what it does or which item it removes.

**Fix:** give the remove button a label that names the item.

1. In [combobox.tsx](../../resources/js/components/ui/combobox.tsx), add a `removeLabel?: string` prop to `ComboboxChip`. Pass it to `ComboboxPrimitive.ChipRemove` as `aria-label={removeLabel ?? 'Remove'}`.
2. Mark the × icon `aria-hidden` so it isn’t read out separately.
3. In [controlled-combobox.tsx](../../resources/js/components/extend/controlled-combobox.tsx), pass ``removeLabel={`Remove ${itemsKeyedByValue[item] || item}`}`` to each `ComboboxChip`.

A screen reader then says, for example, “Remove Sawmill, button”.

**Related, worth fixing at the same time:** the button is styled at 50% opacity until hovered, so the × is likely below the 3:1 minimum for icons. Use full opacity, or at least 70%, and check it in manual Test 4. It is also `icon-xs` size, so check it against the 24 × 24 pixel minimum tap target in manual Test 7.

## 2. Color contrast

Thirteen text-and-background color pairs fall below the 4.5:1 minimum for normal text. They come from five sources, each fixed in one place. Where a pair could be fixed by changing either color, the fix changes the one used less often, so the site’s look shifts as little as possible. Every replacement aims for at least 4.6:1, a small safety margin.

All fixes below are in [app.css](../../resources/css/app.css) or in the page files named. Each one keeps the same hue, slightly darker or more opaque.

### Token changes in app.css

| Token | Used on | Problem | Change | Why this color, not the other | New ratio |
| --- | --- | --- | --- | --- | --- |
| `--muted-foreground` | Grey helper text on forms (“Required fields are marked…”, intro paragraphs) | #737373 on beluga #f0f1f2 = 4.19 | oklch(0.556 0 0) → **oklch(0.53 0 0)**, #6c6c6c | Beluga is the site-wide background; muted text appears in 45 places, all on light backgrounds, so darkening it only helps | 4.64 (5.25 on white) |
| `--destructive` | Form error messages and error field borders | #e7000b on beluga = 4.32 | oklch(0.577 0.245 27.325) → **oklch(0.54 0.245 27.325)**, #d90000 | Used in 14 places versus beluga everywhere. Also improves white text on red buttons, from 4.77 to 5.32 | 4.70 |
| `--color-nature` | Green backgrounds: state page sections, 404 page, review page header, footer | Beluga text on nature #6a7666 = 4.22 | oklch(0.551 0.028 137.85) → **oklch(0.53 0.0282 137.85)**, #647060 | Nature is a background in 15 places, while beluga text appears in 62. It is about 2% darker and hard to see | 4.61 (white text: 5.21) |

Darkening nature lowers the contrast of any dark text placed on it. No page currently puts dark text directly on nature, so nothing else needs to change.

### Class changes on the review pages

These are on the mill-edit review pages ([mill-edit-show.tsx](../../resources/js/pages/mill-edit-show.tsx) and [review-new-mill.tsx](../../resources/js/pages/review-new-mill.tsx)). Only reviewers see them, through links in notification emails, so they don’t need to match brand colors closely.

| Element | Current | Problem | Change | New ratio |
| --- | --- | --- | --- | --- |
| Unchanged rows in the comparison table | `opacity-60` on the whole row | Dims every cell to between 4.17 and 4.30 | Remove `opacity-60`. Changed rows already stand out with a green highlight and a “changed” badge | 7.6 or higher |
| “Old value” cell in changed rows | `opacity-60` on the cell | 3.91 | `text-beluga/75` instead of opacity | 5.12 |
| New values and the “changed” badge | `text-emerald-400` | 3.80 on the badge, 4.41 in cells | **`text-emerald-300`** (also the ring: `ring-emerald-300/20`) | 4.87 on the badge, 5.65 in cells |
| “(Not set)” placeholder | `text-beluga/60` | 2.13–4.36 depending on row | **`text-beluga/75`** | 5.12 or higher |
| “[] (Empty Collection)” placeholder | `text-slate-500` | 1.80–2.12 | **`text-beluga/75`**, matching “(Not set)” | 5.12 or higher |

Also remove the leftover classes that start with `X`, such as `Xtext-slate-300`. They do nothing, but they make it hard to see which color applies.

### Class change on the state page

| Element | Current | Problem | Change | New ratio |
| --- | --- | --- | --- | --- |
| Small descriptions under state contacts ([state-page.tsx](../../resources/js/pages/state-page.tsx)) | `text-white/56` on aircraft #34505a | 3.95 | **`text-white/70`** | 5.18 |

### Not tested yet

The audit checks text at rest. Hover and focus colors, text over images, and icons still need the manual Test 4 in the test plan.

## 3. Map markers have no accessible name

**Severity:** serious. **Where:** Mill map and Single mill.

**Problem:** Leaflet makes each marker a keyboard-focusable button, which is good. But the marker contains only a pin icon, so a screen reader announces “button” with no mill name. A keyboard user tabbing through 20 markers hears “button” 20 times.

**Fix:** Leaflet’s `title` option names the marker. React-leaflet passes it straight through.

1. In [mill-map-marker.tsx](../../resources/js/components/mill-map-marker.tsx), add `title={mill.mill_name}` to `MapMarker`.
2. Do the same in [mill-single-map.tsx](../../resources/js/components/mill-single-map.tsx).
3. In [mill-map.tsx](../../resources/js/components/mill-map.tsx), the search-location marker isn’t interactive, so give it `keyboard={false}` and `interactive={false}` to keep it out of the Tab order. If it ever gets a popup, give it `title="Your search location"` instead.
4. In `MapMarker` in [map.tsx](../../resources/js/components/ui/map.tsx), check that the rendered icon carries `aria-hidden="true"`, so the SVG isn’t read on its own. Recent Lucide icons add it automatically. If it’s missing, wrap the icon in a span with `aria-hidden` before `renderToString`.

**Check by hand afterwards** (manual Test 6): Enter opens a marker’s popup, Esc closes it, and focus returns to the marker. Cluster bubbles should also be reachable and announce how many mills they hold. The audit didn’t flag cluster bubbles, but it doesn’t test what they announce.

## 4. Sideways-scrolling table can’t be reached by keyboard

**Severity:** serious. **Where:** both review pages, at phone width.

**Problem:** on narrow screens, the current-versus-proposed table is wider than the screen and scrolls sideways inside a box. The box can’t receive keyboard focus, and the table has no links or buttons, so keyboard users can’t scroll it and never see the right-hand columns.

**Fix:** make the scroll box focusable and give it a name. In [mill-edit-show.tsx](../../resources/js/pages/mill-edit-show.tsx) and [review-new-mill.tsx](../../resources/js/pages/review-new-mill.tsx), on the `div` with `overflow-x-auto` that wraps the table:

1. Add `tabIndex={0}` so it can take focus; arrow keys then scroll it.
2. Add `role="region"` and `aria-label="Current and proposed values"` so a screen reader says what was focused.
3. Add a visible focus style, such as `focus-visible:ring-2 focus-visible:ring-emerald-300`.

The JSON blocks under “Supplemental Information” and “Full schema” on the same pages use the same scrolling pattern. The audit didn’t flag them only because the test data was short. Apply the same three attributes, with fitting labels.

## 5–7. Page landmarks

**Severity:** moderate. **Where:** every page.

Landmarks are the labeled regions a screen reader lets users jump between: header, navigation, main content and footer. They work like a table of contents for the page. All three issues are fixed in the shared layout, so each fix applies everywhere at once.

### 5. Header and footer aren’t marked as landmarks

**Problem:** the site header and footer are built from plain `div`s. The logo link, the footer logos and anything else outside the menus belong to no landmark, so users jumping between regions skip them.

**Fix:**

1. In [app-header.tsx](../../resources/js/components/app-header.tsx), change the outer `div` (the one with `bg-zucchini`) to a `header` element. Include the breadcrumb bar below it if it belongs to the header.
2. In [app-footer.tsx](../../resources/js/components/app-footer.tsx), change the outer `div` (the one with `bg-nature`) to a `footer` element.

No styling changes are needed. These tags have no default look in Tailwind.

### 6. Header and footer menus share the label “Main”

**Problem:** the navigation menu component labels every menu “Main” unless told otherwise. The header menu and the first footer menu both use that default, so a screen reader’s landmark list shows two “Main navigation” entries with no way to tell them apart. The second footer menu is already labeled “Secondary”. On phones only one shows, because the header menu is hidden there.

**Fix:** in [app-footer.tsx](../../resources/js/components/app-footer.tsx), add `aria-label="Footer"` to the first `NavigationMenu`. Consider renaming “Secondary” to something descriptive of its links, such as “Legal” or “Site information”. Leave the header menu as “Main”.

### 7. A main area nested inside the main area

**Problem:** the shared layout already wraps every page in `<main id="main-content">`. Both review pages add a second `<main>` inside it. A page should have exactly one main area, and it shouldn’t be nested.

**Fix:** in [mill-edit-show.tsx](../../resources/js/pages/mill-edit-show.tsx) and [review-new-mill.tsx](../../resources/js/pages/review-new-mill.tsx), change `<main className="space-y-10">` to `<div className="space-y-10">`. [dump-trunk.tsx](../../resources/js/pages/dump-trunk.tsx) has the same pattern. It isn’t routed, so fix it or delete it.

## Lint findings

The source-code checker (eslint-plugin-jsx-a11y, recommended rules) flags 5 spots across all React components. One is a real problem. The other four are fine as they are and should be silenced with a comment that says why.

The checker reads component code rather than rendered pages, so it overlaps little with the audit above. It is now part of the project’s ESLint setup and checks new code as it’s written. It is configured to treat the project’s `Button`, `Input`, `Label`, `Textarea` and Inertia `Link` components as the HTML elements they render.

| File | Rule | Verdict | Fix |
| --- | --- | --- | --- |
| [mill-list-skeleton.tsx:37](../../resources/js/components/mill-list-skeleton.tsx#L37) | Link has no valid destination | Real problem | The loading placeholder wraps its fake heading in a link to `#`. Keyboard and screen-reader users can land on a link that goes nowhere. Replace the `Link` with a `div`, mark the placeholder `aria-hidden="true"`, and set `aria-busy="true"` on the list while it loads |
| [mill-filters.tsx:343](../../resources/js/components/mill-filters.tsx#L343) | Avoid autofocus | Fine as is | The address field only appears after the user asks to enter a custom location, so moving focus into it is the helpful behavior. Add `// eslint-disable-next-line jsx-a11y/no-autofocus` with that reason |
| [field.tsx:113](../../resources/js/components/ui/field.tsx#L113) | Label not linked to a field | False alarm | `FieldLabel` is a shared wrapper. Its callers supply `htmlFor`, and the audit confirmed the form labels are linked. Silence with a comment |
| [input-group.tsx:64](../../resources/js/components/ui/input-group.tsx#L64) (2 rules) | Click handler without keyboard handler | False alarm | Clicking the area beside the input focuses the input, a mouse convenience. Keyboard users reach the input directly with Tab. Silence both rules with a comment |

The lint workflow in GitHub Actions will report these 5 as errors until they’re fixed or silenced.

## How to verify a fix

Run these from the project root. The automated checks pass when the a11y tests pass at level 1. That is the standing bar in the test suite.

| Check | Command | What passing means |
| --- | --- | --- |
| Critical and serious issues | `sail test --group=a11y` | No critical or serious axe issues on any page or state |
| Everything, including moderate and minor | `sail bash -c 'A11Y_LEVEL=3 php artisan test --group=a11y'` | No issues at all; the target once issues 5–7 are fixed |
| One page | `sail test --group=a11y --filter="state page"` | Quick check while working on one page |
| Source-code lint | `sail npx eslint resources/js` | No jsx-a11y errors (this skips the auto-fix that `npm run lint` applies) |

After the automated checks pass, run the relevant manual tests from the [test plan](manual-test-plan.md). Tests 1, 2 and 6 matter most for issues 1, 3 and 4.
