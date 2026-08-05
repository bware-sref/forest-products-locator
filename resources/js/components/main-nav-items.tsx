import { 
    millMap,
    millList,
    states,
    // stateResources,
    addBusiness,
    faqs,
    contact
} from '@/routes';
import { 
    type NavItem 
} from '@/types';

const mainNavItems: NavItem[] = [
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
