# Forest Products Network — Manual Accessibility Test Plan

2026-10-30 · Bob Ware

## Purpose and target

We are testing the public Forest Products Network site against **WCAG 2.2 Level AA**. If that turns out to be too much work, we may fall back to 2.1 AA.

Automated tests already run the axe-core checker on every public page. They catch problems a machine can measure, such as missing labels, low contrast and invalid ARIA. Industry estimates say automated tools find only about a third of WCAG failures. This manual pass covers the rest: whether the site works with a keyboard, with a screen reader, when zoomed, and on a phone.

**In scope:** the public site, including the forms and the mill-edit review pages that reviewers reach from email links.

**Out of scope for this round:** the admin panel at /admin.

New to screen readers? Start with the step-by-step VoiceOver scripts in Part 3 of [tester-scripts.md](../testing/tester-scripts.md); this plan is the fuller reference.

Each tester does not need to run every test. Pick a test from Tests 1–7, work through the pages in the checklist, and log what you find.

## Setup

Test on the QA site, not production, because some tests submit forms. Open question: confirm the QA URL and who sends testers the mill-edit review links.

| What | Why | Where to get it |
| --- | --- | --- |
| Chrome or Edge, latest | Main browser for most tests | Already installed on most machines |
| Firefox, latest | Second browser for keyboard and zoom checks | mozilla.org |
| NVDA screen reader (Windows) | Test 2 | nvaccess.org, free |
| VoiceOver (Mac and iPhone) | Test 2, if you use Apple devices | Built in: Cmd+F5 on Mac; Settings → Accessibility on iPhone |
| TalkBack (Android) | Test 7, optional | Built in: Settings → Accessibility |
| axe DevTools extension | Quick spot checks of a single page state | Chrome Web Store, free version is enough |
| WebAIM Contrast Checker | Test 4 | webaim.org/resources/contrastchecker |
| Text spacing bookmarklet | Test 3 | Search “text spacing bookmarklet” (Steve Faulkner’s version) |

Before you start:

1. Use a real email address you can check when submitting forms on QA.
2. If you have never used a screen reader, spend 15 minutes on NVDA’s or VoiceOver’s built-in tutorial before Test 2.
3. Turn off browser extensions that change pages, such as ad blockers or dark-mode tools. They can hide or cause problems.

## Pages and states to cover

Run each test on every page below, including the states listed for that page. Those states only appear after you interact with the page, so automated tests mostly miss them.

| Page | Address | States to test |
| --- | --- | --- |
| Home | / | Main menu open on a phone-sized window |
| Mill map | /mill-map | Search by location and radius; filters applied; a map marker popup open; clustered markers expanded |
| Mill list | /mill-list | Search results; no results; filters; paging; sorting |
| Single mill | /mill-list/{mill} | A mill with full details; a mill missing optional details |
| Add a business | /add-business | Empty form; submitted with errors; successful submission |
| Edit a mill | /mills/{mill}/edit (linked from a mill page) | Same as Add a business; also submitting with nothing changed |
| Review an edit | /mill-edits/{link from email} | Changes shown side by side; approve; reject |
| Review a new business | /mill-edits/{link from email} | Approve; reject |
| FAQs | /faqs | Every category; any expanding questions open and closed |
| About us | /about-us | — |
| States | /states | — |
| A state page | /states/{state}, such as /states/georgia | State resources and links |
| Contact | /contact | Empty form; submitted with errors; successful submission |
| Error page | Any address that doesn’t exist, such as /no-such-page | — |

Also check these on every page:

- [ ] Site header, main menu and footer
- [ ] Skip link, if the page has one
- [ ] Page title in the browser tab
- [ ] Any message that appears after submitting a form

## How to record findings

Log one row per problem, per page. If the same problem appears on many pages, such as in the header, log it once and write “all pages”. Open question: where the shared log will live (a spreadsheet or issue tracker).

| Field | What to write | Example |
| --- | --- | --- |
| Page | Address and state | /mill-list, after a search with no results |
| Test | Which test found it | Test 2 – Screen reader |
| What happened | What you did and what went wrong, in plain words | “No results” appears on screen but NVDA says nothing |
| What you expected | What should have happened | NVDA announces “0 mills found.” |
| Severity | One of the four levels below | High |
| WCAG criterion | If you know it; leave blank otherwise | 4.1.3 Status Messages |
| Browser and assistive tech | Versions | Chrome 141, NVDA 2026.1, Windows 11 |
| Evidence | Screenshot or short screen recording | Link to the file |

**Severity levels**

- **Blocker:** someone cannot finish a task at all, such as being unable to submit a form with a keyboard.
- **High:** the task is possible but very hard or confusing, such as errors that a screen reader never announces.
- **Medium:** an annoyance with a workaround, such as an illogical focus order.
- **Low:** a minor or cosmetic issue, such as a slightly unclear link name.

When unsure, choose the higher severity. The developers can adjust it.

## Test 1 – Keyboard only

Everything on the site must work without a mouse or touchpad. Put the mouse out of reach before you start.

**Keys you will use**

- **Tab** and **Shift+Tab**: move forward and back between links, buttons and fields
- **Enter**: follow a link or press a button
- **Space**: press a button, tick a checkbox, or scroll the page
- **Arrow keys**: move within radio buttons, select boxes, menus and the map
- **Esc**: close a menu, popup or dialog

**Steps for each page**

1. Load the page and press Tab once. The first stop should be a “Skip to main content” link or the site logo.
2. Keep pressing Tab through the whole page. On every stop, check that you can see where focus is. Look for a clear outline or highlight, not just a faint color change.
3. Check that focus moves in a sensible order: top to bottom, left to right, in the same order things appear on screen.
4. Check that focus never lands on something hidden or off screen, such as a closed menu.
5. Check that a sticky header or popup never fully covers the item that has focus.
6. Use every control with the keyboard alone: menus, filters, select boxes, checkboxes, paging links and form buttons.
7. Open every menu, popup and dialog. Check that Esc closes it and that focus then returns to the control that opened it.
8. Check that you never get stuck. You should always be able to Tab out of any area, including the map.
9. On forms, submit with Enter and with the submit button. After an error, check that focus moves to the error summary or the first field with an error.

**It passes when** you can reach and use everything, always see where you are, and never get stuck or lost.

WCAG criteria: 2.1.1 Keyboard, 2.1.2 No Keyboard Trap, 2.4.3 Focus Order, 2.4.7 Focus Visible, 2.4.11 Focus Not Obscured (new in 2.2).

## Test 2 – Screen reader

A blind user should get the same information and be able to do the same tasks as a sighted user. Use NVDA with Chrome on Windows, or VoiceOver with Safari on a Mac. Both together is best.

**Key commands**

| Action | NVDA (Windows) | VoiceOver (Mac) |
| --- | --- | --- |
| Turn on or off | Ctrl+Alt+N | Cmd+F5 |
| Stop speaking | Ctrl | Ctrl |
| Read from here | NVDA+Down arrow | VO+A |
| Next or previous item | Down or Up arrow | VO+Right or Left arrow |
| Next heading | H | VO+Cmd+H |
| Next landmark (header, nav, main, footer) | D | VO+U, then choose Landmarks |
| Next form field | F | VO+Cmd+J |
| List all links, headings or landmarks | NVDA+F7 | VO+U |

The NVDA key is Insert by default. The VO key is Ctrl+Option.

**Steps for each page**

1. Load the page and listen. The page title should say which page you are on, such as “Mill List – Forest Products Network”.
2. Open the headings list. There should be exactly one top-level heading that names the page. Other headings should follow a logical outline and not skip levels for no reason.
3. Open the landmarks list. You should find at least a header, a navigation, a main area and a footer.
4. Open the links list. Every link should make sense on its own. “Click here” and repeated “More” links fail.
5. Read the whole page top to bottom. Check that images with meaning are described, and that decorative images are skipped silently.
6. Move through each form field. Each should announce its label, whether it is required, and its current value. Checkbox groups should announce the group name, such as “Mill types”.
7. Submit a form with errors. The errors should be announced without you having to search for them, and each error should be read with its field.
8. Run a search or apply a filter. The screen reader should say “Loading mills…”, then the count (for example “12 mills found.” or “0 mills found.”), without moving focus. With no matches, the page shows “No mills match these filters.” and a Clear filters button, not loading placeholders.
9. Check that buttons and links are announced with the right role. A button that looks like a link should still say “button”.

**It passes when** you can understand every page and finish every task by listening alone.

WCAG criteria: 1.1.1 Non-text Content, 1.3.1 Info and Relationships, 2.4.2 Page Titled, 2.4.4 Link Purpose, 2.4.6 Headings and Labels, 3.3.1 Error Identification, 3.3.2 Labels or Instructions, 4.1.2 Name, Role, Value, 4.1.3 Status Messages.

## Test 3 – Zoom, reflow and text spacing

People with low vision enlarge pages. Nothing should be cut off, overlap, or need sideways scrolling.

**Steps for each page**

1. Set the browser window to about 1280 pixels wide, then zoom to **200%** with Ctrl and + (Cmd and + on a Mac). All text and controls should still be readable and usable.
2. Zoom to **400%**. The page should reflow into a single column. You should only scroll up and down, never sideways. The map and wide data tables are allowed exceptions.
3. Reset zoom with Ctrl+0. In Chrome settings, set the font size to “Very large”. Text should grow without overlapping or being cut off.
4. Run the text spacing bookmarklet. It increases line, letter and word spacing. Text should stay readable and inside its boxes.
5. Rotate a phone or tablet between portrait and landscape. The site should work in both.

**It passes when** every page stays usable at 200% and 400% zoom, with large text, and with extra spacing.

WCAG criteria: 1.3.4 Orientation, 1.4.4 Resize Text, 1.4.10 Reflow, 1.4.12 Text Spacing.

## Test 4 – Color, contrast and visual cues

The automated tests check most text contrast. This test covers what they miss: text over images, hover and focus states, icons, and meaning shown only by color.

**Steps for each page**

1. Check text placed over photos or the map, such as hero banners and map labels. Use the contrast checker’s eyedropper on the lightest part of the image behind the text.
2. Hover over links and buttons, and give them focus. Text must still have enough contrast in every state.
3. Check icons, field borders, checkbox outlines and focus outlines against the color next to them.
4. Look for anything shown only by color, such as required fields marked only in red, or map markers that differ only by color. There must also be text, an icon or a pattern.
5. Links inside paragraphs must be underlined, or otherwise clearly different from the text around them, without relying on color.
6. Turn on Windows High Contrast (Settings → Accessibility → Contrast themes). Buttons, fields, focus outlines and icons should stay visible.

**Minimum contrast ratios**

| Element | Ratio |
| --- | --- |
| Normal text | 4.5 : 1 |
| Large text (24px, or 19px bold) | 3 : 1 |
| Icons, field borders, focus outlines | 3 : 1 |

**It passes when** all text and controls meet these ratios in every state, and nothing relies on color alone.

WCAG criteria: 1.4.1 Use of Color, 1.4.3 Contrast (Minimum), 1.4.11 Non-text Contrast.

## Test 5 – Forms, validation and errors

Test the Add a business, Edit a mill and Contact forms. Do this test with a keyboard, and repeat steps 3–5 with a screen reader.

**Steps for each form**

1. Every field has a visible label that stays visible while you type. Placeholder text alone is not a label.
2. Required fields are marked in text, such as “(required)”, not only with color or an asterisk that is never explained.
3. Submit the form empty. Each error message should say what is wrong and how to fix it, such as “Enter a ZIP code with 5 digits”, not just “Invalid”.
4. Each error should appear next to its field and be linked to it, so a screen reader reads the error when you reach the field.
5. After a failed submit, everything you already typed should still be there.
6. Fix the errors and submit. A clear success message should appear and be announced by the screen reader.
7. Fields for a name, email, phone or address should offer browser autofill.
8. You should never have to type the same information twice in one form (new in 2.2). A “same as physical address” option counts as a fix.
9. Any time limit, such as on the review links, must be explained, and users must be warned before it runs out.
10. Check that there is no CAPTCHA or visible puzzle. The forms use a hidden spam trap, so testers should never see one. Report it if you do.

**It passes when** someone using only a keyboard, or only a screen reader, can find, fix and submit every form without help.

WCAG criteria: 1.3.5 Identify Input Purpose, 3.3.1 Error Identification, 3.3.2 Labels or Instructions, 3.3.3 Error Suggestion, 3.3.7 Redundant Entry.

## Test 6 – Map, search results and other dynamic content

The mill map is the hardest part of the site to make accessible. It does not have to be fully usable without sight, but there must be an equal way to get the same information. That is the Mill list.

**Map steps**

1. From the map page, the Mill list must be easy to find with a keyboard and a screen reader, near the top of the page.
2. Tab to the map. Check that you can pan with the arrow keys and zoom with + and −, and that focus is visible on the map.
3. Tab through the markers. Each should say which mill it is, and Enter should open its popup.
4. In an open popup, check that you can Tab to its links, that Esc closes it, and that focus returns to the marker.
5. Check that a cluster of markers can be expanded with the keyboard.
6. Check that scrolling the page with a mouse wheel or touch doesn’t get trapped by the map.
7. Check that every map action that uses dragging, such as panning or drawing a search area, also has a button or keyboard option (new in 2.2).
8. Compare results for one search on the map and the list. Both should show the same mills.

**Search and filter steps**

1. After a search or filter, the screen reader should announce the number of results (“0 mills found.” when nothing matches). If the search fails, it should announce “Couldn’t load mills.” and the page should offer a Try again button.
2. Focus should not jump unexpectedly when results update.
3. Changing a filter should not reload the page or move you elsewhere without warning, unless there is an “Apply” button.

**It passes when** someone who cannot use the map can find the same mills through the list, and the map itself works with a keyboard.

WCAG criteria: 2.1.1 Keyboard, 2.5.7 Dragging Movements (new in 2.2), 3.2.2 On Input, 4.1.3 Status Messages.

## Test 7 – Content, structure, motion and mobile

These checks cover consistency, motion and touch screens. Use a phone for steps 5–8.

**Content and structure**

1. The header, menu and footer are in the same place and order on every page, and the same controls have the same names everywhere.
2. The help or contact link is in the same place on every page (new in 2.2).
3. Link text says where the link goes. Links that open a new tab or download a file say so.
4. Nothing moves, blinks or plays automatically for more than 5 seconds unless it can be paused. Turn on “reduce motion” in your operating system and check that animations are reduced.

**Mobile and touch**

5. Every button, link and checkbox is easy to tap without hitting its neighbor. Targets should be at least 24 × 24 pixels, or have space around them (new in 2.2).
6. Nothing requires a two-finger or multi-step gesture without a simple alternative. The map’s two-finger pan counts only if the Mill list is available.
7. Turn on TalkBack (Android) or VoiceOver (iPhone) and repeat the main tasks: search for a mill, open it, and send a contact message.
8. Pinch-zoom works on every page and is never blocked.

**It passes when** the site behaves consistently and works comfortably by touch and with a phone screen reader.

WCAG criteria: 2.2.2 Pause, Stop, Hide, 2.5.1 Pointer Gestures, 2.5.8 Target Size (Minimum), 3.2.3 Consistent Navigation, 3.2.4 Consistent Identification, 3.2.6 Consistent Help.

## WCAG 2.2 additions quick reference

WCAG 2.2 adds six criteria at Level A or AA compared with 2.1. Five apply to the public site. It also drops 4.1.1 Parsing, so duplicate-ID warnings matter only when they break assistive technology.

| Criterion | Level | What it means here | Covered in |
| --- | --- | --- | --- |
| 2.4.11 Focus Not Obscured (Minimum) | AA | Sticky headers or popups never fully hide the focused item | Test 1 |
| 2.5.7 Dragging Movements | AA | Map panning and drawing also work with buttons or keys | Test 6 |
| 2.5.8 Target Size (Minimum) | AA | Tap targets at least 24 × 24 px, or spaced apart | Test 7 |
| 3.2.6 Consistent Help | A | Contact and help links stay in the same place | Test 7 |
| 3.3.7 Redundant Entry | A | No retyping the same information in one form | Test 5 |
| 3.3.8 Accessible Authentication (Minimum) | AA | Not applicable: the public site has no login | — |

Full text of each criterion: [WCAG 2.2 Understanding docs](https://www.w3.org/WAI/WCAG22/Understanding/). Overview of what changed: [What’s New in WCAG 2.2](https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/).
