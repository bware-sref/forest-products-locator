import { Icon } from '@/components/icon';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { cn, isSameUrl } from '@/lib/utils';
import { millMap, millList, stateResources, addBusiness, faq, contact } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import sgsfLogo from '@img/southern-group-of-state-foresters_logo_white_horizontal@2x.png';

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
        href: stateResources(),
    },
    {
        title: 'Add Your Business',
        href: addBusiness(),
    },
    {
        title: 'FAQ',
        href: faq(),
    },
    {
        title: 'Contact',
        href: contact(),
    },
];

const sGSF: NavItem = {
    title: 'Southern Group of State Foresters',
    href: 'https://southernforests.org',
};

const activeItemStyles =
    'text-velvet-900 dark:bg-velvet-800 dark:text-velvet-100';


export function AppFooter() {
    const page = usePage<SharedData>();
    return (
        <>
            <div className="bg-nature text-beluga my-8 w-full">
                <div className="mx-auto flex flex-col px-5 md:flex-row lg:h-20 items-center md:max-w-6xl lg:max-w-7xl ">
                    {/* Desktop Navigation */}
                    <div className="h-full items-stretch md:items-center space-x-6 flex flex-col md:flex-row w-full">
                        <NavigationMenu className="flex flex-col md:flex-row justify-stretch h-full items-stretch md:-ml-4 w-full md:w-auto max-w-full">
                            <NavigationMenuList className="flex flex-col md:flex-row h-full items-center justify-items-start md:items-stretch md:space-x-2 md:justify-items-end">
                                {mainNavItems.map((item, index) => (
                                    <NavigationMenuItem
                                        key={index}
                                        className="relative flex h-full items-center"
                                    >
                                        <Link
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                isSameUrl(
                                                    page.url,
                                                    item.href,
                                                ) && activeItemStyles,
                                                'h-9 cursor-pointer px-3 bg-none font-bold text-xl',
                                            )}
                                        >
                                            {item.icon && (
                                                <Icon
                                                    iconNode={item.icon}
                                                    className="mr-2 h-4 w-4"
                                                />
                                            )}
                                            {item.title}
                                        </Link>
                                        {isSameUrl(page.url, item.href) && (
                                            <div className="absolute bottom-0.5 left-0 h-0.5 w-full translate-y-px bg-beluga dark:bg-beluga"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                        <div className="mx-auto md:ml-auto flex max-w-42 justify-self-end">
                            <Link
                                href={sGSF.href}
                                className=""
                                target="_blank"
                            >
                                <img 
                                    src={sgsfLogo}
                                    alt={sGSF.title + ' logo'}
                                    title={sGSF.title}
                                    className="w-full h-full"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
