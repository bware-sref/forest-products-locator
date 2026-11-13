# Tester Scripts: UAT and VoiceOver

As of 2026-11-02. This is the copy to keep. Testers can also use the online copy, which has pick-lists in the results table: https://claude.ai/artifact/RGx1j5otDhJh4ZvGkXeCdn

## Before you start

These scripts walk you through the Forest Products Network site task by task: what to do, and what should happen. Do them in order within a part; the parts can be done separately, by different people.

| Part             | What it covers                                                               | Who                                | Time                                       |
| ---------------- | ---------------------------------------------------------------------------- | ---------------------------------- | ------------------------------------------ |
| 1. Public site   | What visitors do: find mills, submit a business, suggest an edit, contact us | Anyone                             | About 60 minutes                           |
| 2. Admin review  | Approving and rejecting what visitors submit, and checking the result        | Testers with a reviewer account    | About 30 minutes                           |
| 3. Accessibility | Using the site by keyboard only, with VoiceOver, and zoomed in               | Anyone with a Mac; iPhone optional | About 90 minutes, can be split across days |

**Use the test site, never the live one.** Your submissions create real records and send real emails.

- Test site address: https://fpn.sref.info
- Reviewer accounts for Part 2: You must use an account with one of the following roles to review Mill submissions: State Agent, Administrator, Superadmin.
- Where review emails go during testing: Emails will be sent to the email address associated with your user account.

**Use made-up data.** Invent business names that start with `TEST`, such as `TEST Oak Ridge Sawmill`, so they are easy to find and clean up. Use your own email address in "Your Email" so you receive any replies.

**Go slowly on forms.** The site limits how often one internet connection can submit: one business submission or contact message per minute, and ten per day. Testers in the same office share a connection. If a submission fails with an error, or the mill list says "We couldn't load the mills", wait a minute and try again, and note it in your results.

**Browsers.** Do Part 1 in the browser you normally use. If you can, repeat a few tasks on a phone. Note your browser and device in your results.

## How to report a problem

Write down every problem, even small or unsure ones; we would rather sort through extras than miss something. Mark each script in the results table at the end, and add one note per problem with these details:

| Detail                  | Example                                                             |
| ----------------------- | ------------------------------------------------------------------- |
| Script and step         | 1.4, step 3                                                         |
| Page address            | https://fpn.sref.info/mill-list                                     |
| What you did            | Typed `pine` in the search box                                      |
| What happened           | The list went blank and stayed blank                                |
| What you expected       | Mills with pine in their name or species                            |
| Browser and device      | Safari 19, MacBook; or Chrome, Pixel 8                              |
| Screenshot or recording | Attach one if you can. On a Mac, Shift-Command-5 records the screen |

Give each problem a severity:

- **Blocker**: you could not finish the task at all.
- **Major**: you finished, but only with a workaround, or the result was wrong.
- **Minor**: confusing wording, odd layout, or a small visual glitch.

Send your notes to: bware@sref.info

## Part 1: Public site

Each script has a goal, the steps, and what you should see. If what you see differs, that is a finding, even if the site still works.

### 1.1 Find mills on the list

1. Open the home page, then go to the mill list (https://fpn.sref.info/mill-list) from the menu.
2. Wait for the list to load.
3. Type a word from a mill name you can see into the **Search mill names...** box. On a narrow window or phone, open **Filters** first.
4. Clear the search box.
5. Type nonsense, such as `zzzz`.
6. Click **Clear filters** in the message that appears.

What you should see:

- Grey placeholder cards while loading, then mill cards, with "N mills found." next to the page title.
- After step 3, only matching mills, and the count updates.
- After step 5, "No mills match these filters." with a **Clear filters** button, and "0 mills found."
- After step 6, the full list returns and the search box is empty.

### 1.2 Filter mills

1. On the mill list, open **Filters** if they aren't showing.
2. Pick a state. Then pick a county in that state.
3. Pick a mill type, then a wood type.
4. Click **Clear Filters** at the bottom of the filters.

What you should see:

- The county list only offers counties in the chosen state.
- Each choice narrows the list and updates the count.
- On a computer, each filter you pick is listed under the page title. Clicking one removes just that filter.
- Clear Filters empties every filter and brings back all mills.

### 1.3 Search by location

1. On the mill list, under **Filter by Location**, click **Use my location** and allow the browser to share your location.
2. Open **Maximum distance** and pick a different distance.
3. Click **Reset Location**.
4. Click **Choose location**, type a city or ZIP code into **Enter an address, city, or zip...**, and look it up.
5. Click **Reset Location**, then **Choose location** again, and try a made-up address, such as `123 Nowhere Road, Atlantis`.

What you should see:

- Right after allowing location, the list narrows to mills within 50 miles, without picking a distance. The panel says "Showing mills within 50 miles of your current location", and on a computer "Within 50 mi of your location" is listed under the page title.
- Changing **Maximum distance** updates the list and the message. The distance list has no option to clear it.
- **Reset Location** brings back all mills and the **Use my location** and **Choose location** buttons.
- A looked-up place filters right away too, and the message names it, such as "Showing mills within 50 miles of Athens, GA".
- A made-up address shows "We couldn't find that location. Try a different address."

### 1.4 Use the map

1. Go to the mill map (https://fpn.sref.info/mill-map).
2. Zoom and drag the map. On a phone, use two fingers.
3. Click a group of mills (a circle with a number) to zoom in, then click a single mill pin.
4. In the pop-up, click **More Information...**
5. Back on the map, use Filters to search for `zzzz`.

What you should see:

- Pins grouped into numbered circles that split apart as you zoom.
- The pop-up shows the mill's name, address, species and mill type.
- **More Information...** opens the mill's page in a new tab.
- After step 5, a notice at the top of the map: "No mills match these filters." with **Clear filters**.

### 1.5 Look at a mill's page

1. From the list, open any mill.
2. Check the details against what the list card showed.
3. Check that phone, email and website links work as links.

What you should see:

- The mill's name, address, contact details, mill types, wood species and a small map with its pin.
- A link: **Update this mill's information**.

### 1.6 Export the list

1. On the mill list, filter to one state.
2. Click **Export**.

What you should see:

- A file named `mills.xlsx` downloads.
- It opens in Excel, Numbers or Google Sheets, and contains only the filtered mills.

### 1.7 Submit a new business

1. Click **Add Your Business** in the menu (https://fpn.sref.info/add-business).
2. Click **Submit** without filling anything in.
3. Fill in the form with a made-up business named `TEST` plus your initials, your own email in **Your Email**, and at least one mill type and wood species.
4. Change **Is the mailing address the same as the physical address?** and check that the mailing address fields show and hide.
5. Click **Submit**.
6. Search the mill list for your business.

What you should see:

- Step 2 shows an error message beside each required field, and the page moves you to the first one.
- Step 5 shows a thank-you or confirmation message.
- Step 6 finds nothing: new businesses appear only after a reviewer approves them (Part 2).

Write down your business name for Part 2.

### 1.8 Suggest an edit to a mill

1. Open any mill's page and click **Update this mill's information**.
2. Check the form is filled in with the mill's current details.
3. Change one or two things, such as the phone number and a wood species. Enter your own email in **Your Email**.
4. Click **Submit**.
5. Reload the mill's page.

What you should see:

- A confirmation message after submitting.
- The mill's page is unchanged: edits appear only after a reviewer approves them (Part 2).

Write down the mill's name and what you changed.

### 1.9 Other pages

1. Visit each page from the menu and footer: State Resources, one state's page, FAQ, Contact, About Us. Privacy Policy and Southern Group of State Foresters link to other websites; just check they open.
2. On FAQ, type a word such as `cookies` into **Search the FAQs**, and click one of the questions listed.
3. On Contact, click **Submit** with the form empty, then send a real message with the subject starting `TEST`.
4. Visit a page that doesn't exist, such as https://fpn.sref.info/no-such-page.

What you should see:

- Every page loads with no broken images or obviously missing text.
- The FAQ search lists the questions whose question or answer contains your words. Clicking one scrolls the page to it, and your search stays in the box.
- The empty contact form shows an error beside each required field; the filled-in one shows "Thank you for submitting a contact request."
- The missing page shows a friendly not-found page with a way back to the site.

## Part 2: Admin review

Every new business and every suggested edit waits for a reviewer before it appears on the site. Reviewers get an email with a link; the review happens on that link's page, not inside the admin panel. The email goes to the site's administrators and to the reviewers for the mill's state; see "Where review emails go during testing" above.

You need the submissions from scripts 1.7 and 1.8, plus one extra of each to reject. Make the extras the same way, with different `TEST` names. **Don't delete anything**: we clean up `TEST` records after testing.

### 2.1 Approve a suggested edit

1. Find the email titled "Mill Edit Submitted (#N)" for your edit from 1.8.
2. Check it shows the mill's name, your email, and when it was sent. Click **Review Edits**.
3. On the review page, compare the current and proposed values.
4. Click **Approve Updates**.

What you should see:

- The review page shows a table of current and proposed values, with your changes clearly marked.
- After approving, you land on the mill's page with "Approved MillEdit #N for Mill #M." and your changes are showing.

### 2.2 Reject a suggested edit

1. Open the review email for your extra edit and click **Review Edits**.
2. Click **Reject Changes**.

What you should see:

- You land on the mill's page with "Rejected MillEdit #N."
- The mill's details are unchanged.

### 2.3 Approve a new business

1. Find the email titled "New Mill Submitted (#N)" for your business from 1.7, and open its review link.
2. Check every detail you entered is listed.
3. Click **Approve Updates**.
4. Search the mill list for the business. Then look for it on the map.

What you should see:

- After approving, you land on the new business's page with an "Approved" message.
- It appears in the mill list straight away.
- Its map pin may take a few minutes: the address is turned into map coordinates in the background. If there is no pin after 15 minutes, note it.

### 2.4 Reject a new business

1. Open the review link for your extra business and click **Reject Changes**.
2. Search the mill list for it.

What you should see:

- You land on the home page with "Rejected MillEdit #N."
- The business does not appear anywhere on the site.

### 2.5 Reuse a review link

1. Open the review link from 2.1 again, after it has been approved.

What you should see:

- A not-found page. A decided review can't be approved or rejected twice.

### 2.6 Check and hide a mill in the admin panel

1. Log in to the admin panel at /admin with your reviewer account.
2. Open **Mills** and search for the business you approved in 2.3.
3. Open it and check the details match what you submitted.
4. Change its phone number and save. Open its page on the public site.
5. Back in the admin panel, change its **Status** to **Pending** and save.
6. Search for it on the public mill list and map.
7. In the admin panel, search for it again, set its Status back to **Approved**, and save.

What you should see:

- If your account is a State Agent, the Mills list shows only your state's mills.
- The admin details match what was submitted and approved.
- The new phone number shows on the public page straight away.
- While Pending, the business disappears from the public list and map, but still shows in the admin Mills list.
- Once Approved again, it is back on the public site.

## Part 3: Accessibility setup

Part 3 checks the site works for people who can't use a mouse, can't see the screen, or need it enlarged. You don't need any experience; spend 15 minutes on this setup first, then the scripts in the next section.

### Keyboard-only browsing

On a Mac, Safari skips links when you press Tab unless you turn it on. Do both of these once:

1. **System Settings > Keyboard**: turn on **Keyboard navigation**.
2. **Safari > Settings > Advanced**: turn on **Press Tab to highlight each item on a webpage**.

Chrome and Firefox tab to links without changes. The keys you need:

| Key             | Does                                             |
| --------------- | ------------------------------------------------ |
| Tab / Shift-Tab | Next / previous link, button or field            |
| Return          | Follow a link, press a button, submit a form     |
| Space           | Press a button, tick a checkbox, scroll the page |
| Arrow keys      | Move within a menu, list of options, or the map  |
| Escape          | Close a menu, pop-up or dialog                   |

### VoiceOver on a Mac

Use **Safari** with VoiceOver; it is the pairing Apple supports best.

1. Turn VoiceOver on and off with **Command-F5**. On a Mac with Touch ID, you can also hold Command and press the Touch ID button three times.
2. The first time, macOS offers a short tutorial. Take it; it takes about 10 minutes.
3. Open **VoiceOver Utility** (System Settings > Accessibility > VoiceOver > Open VoiceOver Utility). Under **Speech**, slow the speaking rate if it is too fast.
4. Leave the **caption panel** on. It shows what VoiceOver says, so you can copy it into your notes.

VoiceOver commands all start with **Control-Option**, written **VO** below. Hold both, then press the other key.

| Keys                                    | Does                                                                                                                                               |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| VO-Right Arrow / VO-Left Arrow          | Next / previous item                                                                                                                               |
| VO-Space                                | Click the current item                                                                                                                             |
| VO-A                                    | Read from here; press **Control** to stop                                                                                                          |
| VO-Command-H                            | Next heading                                                                                                                                       |
| VO-Command-J                            | Next form field                                                                                                                                    |
| VO-U                                    | The rotor: lists of headings, links, form fields and page regions. Left/Right Arrow switches list, Up/Down picks, Return goes there, Escape closes |
| VO-Shift-Down Arrow / VO-Shift-Up Arrow | Step into / out of a group, such as a table                                                                                                        |

Tab and Shift-Tab still work while VoiceOver is on, and VoiceOver reads each item you land on.

### VoiceOver on an iPhone (optional)

Set up the shortcut **before** turning VoiceOver on, because VoiceOver changes how touch works.

1. **Settings > Accessibility > Accessibility Shortcut**: choose **VoiceOver**. Now three quick presses of the side button turn it on and off.
2. Use Safari.

| Gesture                            | Does                                                                                            |
| ---------------------------------- | ----------------------------------------------------------------------------------------------- |
| Tap                                | Select an item and hear it                                                                      |
| Double-tap                         | Activate the selected item                                                                      |
| Swipe right / left with one finger | Next / previous item                                                                            |
| Two-finger tap                     | Pause or resume speech                                                                          |
| Three-finger swipe up / down       | Scroll                                                                                          |
| Twist two fingers like a dial      | The rotor: choose headings, links or form controls; then swipe down / up to move by that choice |

### Zoom and display settings

- **Command-plus / Command-minus** zooms the page in the browser; **Command-0** resets it.
- **System Settings > Accessibility > Display**: **Reduce motion** and **Increase contrast** are used in script 3.6.

## Part 3: Accessibility scripts

In these scripts, "you should hear" means roughly these words; exact wording varies. Write down what VoiceOver actually said (copy it from the caption panel) whenever it differs, says nothing, or says something confusing.

### 3.1 Keyboard only (no VoiceOver)

Put the mouse or trackpad out of reach. Do this on the home page, mill list, mill map, one mill's page, add-a-business, and contact.

1. Load the page and press **Tab** once.
2. Keep pressing Tab through the whole page, then Shift-Tab back a few steps.
3. Use every menu, button, filter and dropdown with the keyboard alone.
4. On a narrow window (or zoomed to 200%), open the menu button at the top, move through it, and press Escape.
5. On the mill map, Tab to the map and use the arrow keys and **+ / -**. Tab to a mill pin and press Return.
6. On the mill list in a full-size window, the Filters panel is open beside the list. Tab to its close button (×) and press Return. Then press Return on **Filters** to open it again.
7. Make the window narrow (or zoom to 200%), open **Filters**, and press Escape.

What you should see:

- The first Tab shows a **Skip to main content** link; Return on it jumps past the menu.
- You can always see where you are: a clear outline or highlight on the current item.
- The order follows the page: top to bottom, left to right. Focus never disappears or gets stuck.
- Everything you can click, you can also reach and use with the keyboard.
- The map pans and zooms with keys; a pin's pop-up opens with Return and closes with Escape.
- In a full-size window, the × hides the Filters panel and focus goes to the **Filters** button, which opens it again. Escape doesn't close this panel, since it isn't a pop-up.
- In a narrow window, Escape closes Filters and the menu, and focus goes back to the button that opened them.

### 3.2 VoiceOver: page structure

Use Safari with VoiceOver on. Do this on the home page, mill list, one mill's page and one state's page.

1. Open the rotor (**VO-U**) and look at the **Headings** list, then the **Landmarks** list.
2. Close the rotor. Press **VO-Command-H** repeatedly to move heading by heading.
3. Press **VO-Right Arrow** through the top of the page and the footer.
4. On a mill's page, find each image and link.

What you should hear:

- Exactly one top-level heading ("heading level 1") naming the page, with sensible headings below it.
- Landmarks including a banner (the header), navigation "Main", main, content information (the footer), and navigations "Footer" and "Site Information".
- Every link says where it goes. No links read as just "link", "click here" or a web address.
- Images are described, or skipped if they are decoration. Nothing reads as a file name such as "IMG_2034.jpg".

### 3.3 VoiceOver: forms

Do this on add-a-business and contact.

1. Move into the form with **VO-Command-J**, field by field.
2. On mill type and wood species, pick two options each, then remove one with its × button.
3. Submit the form empty.
4. Move to the first field with an error.
5. Fill in the form correctly and submit.

What you should hear:

- Each field's name, its type ("edit text", "pop-up button") and whether it is required.
- On the × buttons: "Remove" plus the option's name, such as "Remove Sawmill, button".
- After an empty submit: the errors are announced, and each field with a problem says "invalid data" and its error message.
- After a correct submit: the confirmation message is read out without you hunting for it.

### 3.4 VoiceOver: searching for mills

1. On the mill list, open **Filters** and type a mill name into the search box.
2. Wait without moving.
3. Type `zzzz` and wait.
4. Move to **Clear filters** in the message and press **VO-Space**.
5. Repeat steps 1-3 on the mill map, then find a mill pin with VO-Right Arrow.

What you should hear:

- "Loading mills…", then "N mills found.", read out on their own while you stay in the search box.
- After `zzzz`: "0 mills found."
- After Clear filters: you land on the page title, such as "Mill List, heading level 1", then hear the new count.
- On the map, each pin reads as the mill's name, and VO-Space opens its pop-up.

### 3.5 VoiceOver: searching the FAQs

1. On the FAQ page, move to **Search the FAQs** with **VO-Command-J** and type `cookies`.
2. Wait without moving.
3. Press **Down Arrow** to move through the results, then **Return** on one.
4. Press **VO-A** to read from there, then press **Control** to stop.
5. Go back to the search box. Clear it, type `location`, and wait. While the results are showing, press **VO-Right Arrow** a few times.
6. Press **Escape**.
7. Type `location` again if it's gone, then press **Tab** while the results are showing.
8. Clear the search box and type `zebra`.

What you should hear:

- On the search box: "Search the FAQs", and that it's a text field (VoiceOver may say "combo box").
- After typing: "N questions found." on its own, while you stay in the search box.
- On each result: the question, then its category, such as "Does the site use cookies? Finding Mills".
- After Return: VoiceOver moves to that question and its answer, and VO-A reads the answer. The search box still has your words in it.
- With the results showing, VO-Right Arrow stays among the results and the search box rather than moving into the page behind. That's expected; write down if it does anything else.
- Escape closes the results and leaves you in the search box.
- **Tab with the results showing:** the results close, and you hear the next item on the page clearly. Write down exactly what VoiceOver said. Silence, "dimmed", or a blank there is a problem.
- After `zebra`: "0 questions found." and "No questions match "zebra"."

### 3.6 Zoom, reflow and display settings

1. Make the browser window about 1280 pixels wide (roughly a laptop screen's full width), then zoom to **200%** (Command-plus several times). Use the mill list, a mill's page, and add-a-business.
2. Zoom to **400%** and repeat.
3. Turn on **Reduce motion**, then reload the home page, map and mill list.
4. Turn on **Increase contrast**, and look over the same pages.

What you should see:

- At 200%, nothing overlaps or is cut off, and all text is readable.
- At 400%, the page becomes a single column. You only scroll up and down, never sideways, except on the map and wide tables.
- With Reduce motion, no large sliding or zooming animations.
- Text and buttons stay readable against their backgrounds, in both settings.

### 3.7 iPhone with VoiceOver (optional)

1. Turn VoiceOver on with the side-button shortcut and open the site in Safari.
2. Swipe right through the home page.
3. Open the menu, go to the mill list, and search with the rotor set to **Form Controls**.
4. Repeat script 3.3 on the contact form.
5. On the FAQ page, search for `cookies` and double-tap a result.

What you should hear:

- The same as 3.2 to 3.5: named buttons and links, a heading for each page, counts announced after a search, and errors read out after submitting.
- Picking an FAQ result moves VoiceOver to that question.

## Results

Each tester fills in their own copy of this table: copy it into a new doc or your notes, or ask for your own copy of this doc. Use **Pass with notes** when it worked but something was off, and **Blocked** when you couldn't start, such as a missing review email.

Result is one of: Not done, Pass, Pass with notes, Fail, Blocked.

| Script                                       | Result   | Browser and device | Problem notes |
| -------------------------------------------- | -------- | ------------------ | ------------- |
| 1.1 Find mills on the list                   | Not done |                    |               |
| 1.2 Filter mills                             | Not done |                    |               |
| 1.3 Search by location                       | Not done |                    |               |
| 1.4 Use the map                              | Not done |                    |               |
| 1.5 Look at a mill's page                    | Not done |                    |               |
| 1.6 Export the list                          | Not done |                    |               |
| 1.7 Submit a new business                    | Not done |                    |               |
| 1.8 Suggest an edit to a mill                | Not done |                    |               |
| 1.9 Other pages                              | Not done |                    |               |
| 2.1 Approve a suggested edit                 | Not done |                    |               |
| 2.2 Reject a suggested edit                  | Not done |                    |               |
| 2.3 Approve a new business                   | Not done |                    |               |
| 2.4 Reject a new business                    | Not done |                    |               |
| 2.5 Reuse a review link                      | Not done |                    |               |
| 2.6 Check and hide a mill in the admin panel | Not done |                    |               |
| 3.1 Keyboard only                            | Not done |                    |               |
| 3.2 VoiceOver: page structure                | Not done |                    |               |
| 3.3 VoiceOver: forms                         | Not done |                    |               |
| 3.4 VoiceOver: searching for mills           | Not done |                    |               |
| 3.5 VoiceOver: searching the FAQs            | Not done |                    |               |
| 3.6 Zoom, reflow and display settings        | Not done |                    |               |
| 3.7 iPhone with VoiceOver                    | Not done |                    |               |
