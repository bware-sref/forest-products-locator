<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The site's real FAQs and their categories, from docs/content/faqs-draft.md. Answers are written in Markdown here and
 * stored as HTML, the same as the admin's rich text editor saves them.
 *
 * Safe to run again: categories are matched by name and FAQs by question, so it updates them rather than adding
 * copies. Edits made in the admin to those FAQs are overwritten; FAQs and categories it doesn't know about are left
 * alone.
 */
class FaqCategorySeeder extends Seeder
{
    /**
     * Category name => [question => Markdown answer], in display order.
     *
     * @var array<string, array<string, string>>
     */
    public const FAQS = [
        'About the Site' => [
            'What is the Forest Products Locator?' => <<<'MD'
                It's a free, searchable directory of primary forest product mills, mainly in the southern United States. You can find mills on a map or in a list, filter by state, county, mill type, and wood type, and contact the mills directly.
                MD,
            'Who runs this site?' => <<<'MD'
                The site is a project of the Services, Utilization, and Marketing (SUM) Task Force of the Southern Group of State Foresters. Southern Regional Extension Forestry provides technical support.
                MD,
            'Who is the site for?' => <<<'MD'
                It's for anyone who needs to find a mill that buys or processes timber, including:
                - landowners and loggers looking for a market for their wood
                - foresters and consultants
                - wood buyers and procurement staff
                - economic development and state agency staff
                - companies thinking about locating in the region
                MD,
            'What is a "primary" forest product mill?' => <<<'MD'
                A primary mill takes logs or wood chips straight from the forest and turns them into a first product. Sawmills that make lumber and pulp mills that make paper are examples. Secondary manufacturers, such as furniture makers, cabinet shops, and box plants, use those first products as their raw material. They aren't included in this directory.
                MD,
            'Which states are covered?' => <<<'MD'
                The directory focuses on the 13 southern states: Alabama, Arkansas, Florida, Georgia, Kentucky, Louisiana, Mississippi, North Carolina, Oklahoma, South Carolina, Tennessee, Texas, and Virginia. Puerto Rico and the U.S. Virgin Islands are also part of the region, and mills from other states are welcome. Listings depend on the data each state shares, so coverage varies from state to state.
                MD,
            'Where does the mill information come from?' => <<<'MD'
                Most listings come from state forestry agencies, which keep their own mill directories and share them with us. Mill owners and site visitors can also submit new mills or corrections. State forestry staff review those submissions before they're published.
                MD,
            'How current is the information?' => <<<'MD'
                It varies by state. Each state agency updates its mill directory on its own schedule, and we update the site when we receive new data. Visitor submissions are added as soon as state staff approve them. Since businesses change, it's a good idea to contact a mill to confirm details before hauling wood.
                MD,
            'Is every mill listed?' => <<<'MD'
                No. The directory only includes mills that state agencies have shared with us or that visitors have submitted. Some mills aren't listed, and some listings may be out of date. If you know of a missing mill, you can add it on the **Add a Business** page.
                MD,
            'Is this a government website?' => <<<'MD'
                Not exactly. The site is run by a partnership of state forestry agencies through the Southern Group of State Foresters, and most of the data comes from those agencies. It isn't an official record of any one state, though.
                MD,
            'Does the site endorse or rate the mills it lists?' => <<<'MD'
                No. A listing only means the mill was reported to us as an active primary forest products business. We don't rate, rank, or recommend mills, and we don't verify prices, buying specifications, or business practices.
                MD,
            'Does the directory show what mills are paying for wood?' => <<<'MD'
                No. The directory doesn't include prices, buying specifications, or quotas. Contact the mill directly for that information.
                MD,
        ],
        'Finding Mills' => [
            'What\'s the difference between the Mill Map and the Mill List?' => <<<'MD'
                They show the same mills with the same filters. The Mill Map shows where the mills are. The Mill List is easier to scan and read. Your filters carry over when you switch between them, and stay set until you close your browser tab.
                MD,
            'How do I narrow down the results?' => <<<'MD'
                Open the **Filters** panel. You can filter by:
                - state and county
                - mill type (for example, sawmill, pulp and paper, chip mill)
                - wood type (for example, hardwood or softwood)
                - distance from a location

                You can also type part of a mill's name in the search box. The number of mills that match your filters is shown at the top of the page. **Clear Filters** resets everything.

                On a computer, the filters you've set are listed under the page title, even with the Filters panel closed. Click one to remove it. On a phone, the **Filters** button shows how many filters are on.
                MD,
            'Can I find mills near a specific location?' => <<<'MD'
                Yes. In the Filters panel, choose **Use my location**, or type an address, city, or ZIP code. You'll see mills within 50 miles of that spot, and you can change **Maximum distance** to anywhere from 5 to 250 miles. Distances are straight-line, not driving miles. To stop filtering by location, choose **Reset Location**.
                MD,
            'Do you save my location?' => <<<'MD'
                No. Your location is only used to find nearby mills, and we don't store it. If you type in an address, it's sent to the U.S. Census Bureau's free address lookup service to get map coordinates, but we don't keep a copy.

                Your browser remembers your filters, including a location you chose, so they carry over between the Mill Map and Mill List. They're kept in your browser only, and are cleared when you close the tab.
                MD,
            'Do you collect any information about visitors?' => <<<'MD'
                We don't store any personal information about visitors. We use Google Analytics to understand how the site is used, such as which pages are visited and what kinds of devices people use. Google may collect and store this information under its own privacy policy. You can opt out with your browser's privacy settings or with Google's [opt-out browser add-on](https://tools.google.com/dlpage/gaoptout). For more details, see the Privacy Policy linked at the bottom of every page.
                MD,
            'Does the site use cookies?' => <<<'MD'
                Yes, a few. The site uses essential cookies to keep it running and its forms secure. Google Analytics also uses cookies to count visits; you can opt out as described above. Your filter choices aren't stored in a cookie: your browser keeps them for the current tab only.
                MD,
            'Why isn\'t "Use my location" working?' => <<<'MD'
                Your browser has to give the site permission to use your location. If you blocked it earlier, change the permission in your browser's site settings and try again. You can also type in an address instead.
                MD,
            'What does the search box search?' => <<<'MD'
                It searches mill names only, not addresses, towns, or products. To search by place, use the state, county, or distance filters.
                MD,
            'Why are wood types so general (hardwood and softwood)?' => <<<'MD'
                Most state directories only record wood at that level, so the site uses the same broad groups. If you need a specific species, such as white oak or yellow pine, contact the mill.
                MD,
            'Why does a mill appear under more than one mill type?' => <<<'MD'
                Many mills make more than one product. A sawmill might also sell chips, mulch, or firewood. A mill can be listed under each type it reports.
                MD,
            'Why do some mill types look alike?' => <<<'MD'
                Each state names mill types its own way, so you may see similar names like "Post & Pole" and "Post Pole." If you don't find what you need under one name, try the similar ones too.
                MD,
            'Can I download the results?' => <<<'MD'
                Yes. Both the Mill Map and the Mill List have an **Export** button that downloads an Excel spreadsheet. The spreadsheet only includes the mills that match your current filters, so set your filters first.
                MD,
            'I found a mill that has closed or has wrong information. What should I do?' => <<<'MD'
                Open the mill's page and choose **Update this mill's information** to send a correction. State forestry staff review the change before it appears. To report a mill that has closed, contact the forestry agency in the mill's state. You'll find agency contacts on the State pages. If no contact is listed for that state, use our Contact page.
                MD,
            'A mill I know of isn\'t listed. Can I add it?' => <<<'MD'
                Yes. Use the **Add a Business** page. You don't need to own the mill to suggest it. State forestry staff will review it before it's published.
                MD,
            'What are the State pages?' => <<<'MD'
                State pages describe each state's forest industry and list contacts at the state forestry agency. Many of these agencies offer free help, such as more information about local wood supply or a free site suitability analysis for businesses looking to locate in the state.
                MD,
        ],
        'Listing Your Mill' => [
            'Does it cost anything to be listed?' => <<<'MD'
                No. Listings are free.
                MD,
            'How do I get my mill listed?' => <<<'MD'
                Fill out the form on the **Add a Business** page. You can also ask your state forestry agency to add you to their mill directory, which is shared with this site.
                MD,
            'What information do I need to provide?' => <<<'MD'
                The form asks for:
                - **Required:** mill name, physical address (street, city, state, and ZIP code), and your own email address so we can follow up and let you know when your submission is approved or rejected
                - **Optional:** mailing address, contact name and title, phone and fax numbers, email, website, mill size, year established, mill types, and wood types

                Contact information is optional, but listings are reviewed faster when state staff can reach you. It also helps buyers and sellers find you.
                MD,
            'How long until my listing shows up?' => <<<'MD'
                Your listing is published once your state forestry agency has reviewed and approved it. How long that takes depends on the agency. We'll email you once it's been approved or rejected. If you haven't heard back after a few weeks, contact your state forestry agency. You'll find agency contacts on the State pages. If no contact is listed for that state, use our Contact page.
                MD,
            'Will I be told when my listing is approved?' => <<<'MD'
                Yes. We'll email you at the address you provided once your submission has been approved or rejected. The same goes for updates to an existing listing.
                MD,
            'How do I update my listing?' => <<<'MD'
                Find your mill on the Mill List or Mill Map, open its page, and choose **Update this mill's information**. Change what you need and submit it. State forestry staff will review the change before it appears.
                MD,
            'How do I remove my listing (for example, if the mill has closed)?' => <<<'MD'
                Contact the forestry agency in your mill's state and ask them to remove it. State agencies manage the mill data this site uses, so removing it at the source keeps the listing from coming back. You'll find agency contacts on the State pages. If no contact is listed for that state, use our Contact page.
                MD,
            'Do I need to renew my listing?' => <<<'MD'
                No. Listings stay up until the state agency removes them from its data. Some state agencies check in with mills from time to time to confirm their information.
                MD,
            'Do you share my information?' => <<<'MD'
                Information in your listing (business name, address, and the contact details you provide) is public and can be downloaded by visitors. Don't include personal phone numbers or emails you don't want shared. The email you enter as the person submitting the form is never shown publicly. Only site administrators and the forestry staff in the mill's state can see it. They use it to verify your submission, ask follow-up questions, and let you know whether it was approved.
                MD,
            'What does "mill size" mean?' => <<<'MD'
                Mill size is a general measure reported by each state. States measure it differently. Some use Small, Medium, and Large, and others use number of employees or their own size codes. Many listings don't include a size. Use it as a rough guide only, and contact the mill for details such as how much wood they buy.
                MD,
        ],
        'For State Agencies' => [
            'How does my agency\'s mill data get onto the site?' => <<<'MD'
                Send your mill directory, usually as a spreadsheet, to the state contact listed on your State page. If your state doesn't have a contact listed yet, send it to the site administrators through the Contact page. We match your columns to the site's fields and load it. Updates can be sent whenever your agency revises its directory.
                MD,
            'Who reviews submissions from the public?' => <<<'MD'
                When someone adds or updates a mill in your state, the site administrators and your agency's designated reviewers get an email with a link to review it. They can approve or reject it. Submissions aren't published until they're approved.
                MD,
            'How can my agency update its State page?' => <<<'MD'
                Contact the site administrators with the text, images, or contact information you'd like changed.
                MD,
        ],
        'Help' => [
            'Can I use the site on my phone?' => <<<'MD'
                Yes. The site works on phones, tablets, and computers. On a phone, the filters open in a panel from the side of the screen.
                MD,
            'Is the site accessible?' => <<<'MD'
                We aim to meet the WCAG 2.2 AA accessibility standard. If something on the site doesn't work for you, please let us know through the Contact page. See our Accessibility page for details.
                MD,
            'How do I contact you?' => <<<'MD'
                Use the Contact page. For questions about a specific mill's business, such as prices or what wood they buy, contact the mill directly.
                MD,
        ],
    ];

    public function run(): void
    {
        $categoryOrder = 0;
        foreach (self::FAQS as $categoryName => $faqs) {
            $categoryOrder += 10;
            $category = FaqCategory::updateOrCreate(['name' => $categoryName], ['order' => $categoryOrder]);

            $faqOrder = 0;
            foreach ($faqs as $question => $answer) {
                $faqOrder += 10;
                // withoutGlobalScopes: an unpublished copy should be updated too, not duplicated
                Faq::withoutGlobalScopes()->updateOrCreate(
                    ['question' => $question],
                    [
                        'answer' => self::toHtml($answer),
                        'order' => $faqOrder,
                        'faq_category_id' => $category->id,
                    ],
                );
            }
        }
    }

    private static function toHtml(string $markdown): string
    {
        return trim(Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }
}
