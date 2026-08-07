import { 
    aboutUs,
    accessibility,
    addBusiness,
    contact,
    faqs,
    millList,
    millMap,
    states,
    // stateResources,
    sitemap,
} from '@/routes';
import { 
    type NavItem 
} from '@/types';

export const primaryNavItems: NavItem[] = [
    {
        title: 'Mill Map',
        href: millMap(),
    },
    {
        title: 'Mill List',
        href: millList(),
    },
    {
        title: 'State Resources',
        href: states(),
    },
    {
        title: 'Add Your Business',
        href: addBusiness(),
    },
    {
        title: 'FAQ',
        href: faqs(),
    },
    {
        title: 'Contact',
        href: contact(),
    },
];

export const secondaryNavItems: NavItem[] = [
    {
        title: 'About Us',
        href: aboutUs(),
    },
    {
        title: 'Accessibility',
        href: accessibility(),
    },
    {
        title: 'Sitemap',
        href: sitemap(),
    },
];

/**
 * This one is the least useful.
 * Okay.
 * What if we just export the SGSF NavItem itself without an array?
 * That way we can still keep all the nav mess in a single config file.
 */
export const sgsfNav: NavItem = {
    title: 'Southern Group of State Foresters',
    href: 'https://southernforests.org',
};