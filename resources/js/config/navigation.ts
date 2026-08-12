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

/**
 * I don't know if we need Accessibility and Sitemap.
 * However, if we don't have those, we need at least one more link.
 */
export const secondaryNavItems: NavItem[] = [
    {
        title: 'About Us',
        href: aboutUs(),
    },
    // {
    //     title: 'Accessibility',
    //     href: accessibility(),
    // },
    // {
    //     title: 'Sitemap',
    //     href: sitemap(),
    // },
    {
        title: 'Privacy Policy',
        href: 'https://policy.uga.edu/policies#/programs/rk-6awCBp?bc=true&bcCurrent=Privacy%20Policy%20and%20EU%20GDPR%20Privacy%20Notice&bcGroup=Information%20Technology%20&bcItemType=programs',
    }
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