import { 
    aboutUs,
} from '@/routes';
import * as mills from '@/routes/mills';
import {
    create as contact,
} from '@/routes/contacts';
import {
    index as faqs,
} from '@/routes/faqs';
import {
    index as states,
} from '@/routes/states';
import { 
    type NavItem 
} from '@/types';

export const primaryNavItems: NavItem[] = [
    {
        title: 'Mill Map',
        href: mills.map(),
    },
    {
        title: 'Mill List',
        href: mills.index(),
    },
    {
        title: 'State Resources',
        href: states(),
    },
    {
        title: 'Add Your Business',
        href: mills.create(),
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
 * Footer/Secondary navigation
 */
export const secondaryNavItems: NavItem[] = [
    {
        title: 'About Us',
        href: aboutUs(),
    },
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