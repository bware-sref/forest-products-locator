import { useState } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Icon } from '@/components/icon';
import { Button } from '@/components/ui/button';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    // SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn, isSameUrl, resolveUrl } from '@/lib/utils';
import { home, millMap, millList, stateResources, addBusiness, faqs, contact} from '@/routes';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { 
    Menu,
    // Search,
    TreePine,
    XIcon,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    // {
    //     title: 'Dashboard',
    //     href: dashboard(),
    //     icon: LayoutGrid,
    // },
    {
        title: 'Mill Map',
        href: millMap(),
        // icon: Map,
    },
    {
        title: 'Mill List',
        href: millList(),
        // icon: List,
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
        href: faqs(),
    },
    {
        title: 'Contact',
        href: contact(),
    },
];

const rightNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/react-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#react',
    //     icon: BookOpen,
    // },
];

const activeItemStyles =
    'text-velvet-900 dark:bg-velvet-800 dark:text-velvet-100';

const mobileActiveItemStyles = '';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

// const displaySearch = false;

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const page = usePage<SharedData>();

    // we could use auth to show a link to admin...
    // const { auth } = page.props;
    
    const [isOpen, setIsOpen] = useState(false);
    return (
        <>
            <div className="border-b-6 border-sidebar-border bg-zucchini text-velvet">
                <div className="mx-auto flex h-22 lg:h-20 items-center px-4 md:max-w-7xl">

                    {/* Logo + home link */}
                    <Link
                        href={home()}
                        prefetch
                        className="flex items-center space-x-2"
                    >
                        <AppLogo />
                    </Link>

                    {/* Mobile Menu */}
                    <div className="lg:hidden ml-auto z-100 relative">
                        <Sheet open={isOpen} onOpenChange={setIsOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="lg"
                                    className="mr-2 h-8.5 w-8.5 [&_svg:not([class*='size-'])]:size-14 ml-auto"
                                >
                                    {isOpen ? (
                                        <XIcon className="h-5 w-5" />
                                    ) : (
                                        <Menu className="h-5 w-5" />
                                    )}
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="flex h-full w-full flex-col items-stretch justify-between bg-sidebar mt-23.5"
                                overlayClassName="bg-transparent"
                                showCloseButton={false}
                            >
                                {/** Laravel includes the SheetTitle */}
                                <SheetTitle className="sr-only">
                                    Navigation Menu
                                </SheetTitle>
                                {/** However, browsers complain about a missing description, let's go redundant! */}
                                <SheetDescription className="sr-only">
                                    Main Navigation Menu.
                                </SheetDescription>
                                {/**
                                 * This header just repeats the logo.
                                <SheetHeader className="flex justify-start text-left">
                                    <Link
                                        href={home()}
                                        prefetch
                                        className="flex items-center space-x-2"
                                    >
                                        <AppLogo />
                                    </Link>
                                </SheetHeader>
                                */}

                                {/** Actual mobile nav */}
                                <div className="flex h-full flex-1 flex-col space-y-4 p-4">
                                    <div className="flex h-full flex-col justify-between text-sm">
                                        <div className="flex flex-col space-y-7">
                                            {mainNavItems.map((item) => (
                                                <Link
                                                    key={item.title}
                                                    href={item.href}
                                                    className={cn(
                                                        isSameUrl(
                                                            page.url,
                                                            item.href,
                                                        ) && mobileActiveItemStyles,
                                                        "flex items-center space-x-2 font-bold text-3xl justify-end"
                                                    )}
                                                    {...(isSameUrl(page.url, item.href) && {"aria-current": "page"})}
                                                >
                                                    {isSameUrl(page.url, item.href) && (
                                                        <Icon
                                                            iconNode={TreePine}
                                                            className="h-5 w-5"
                                                        />
                                                    )}
                                                    <span>{item.title}</span>
                                                </Link>
                                            ))}
                                        </div>

                                        {/** we don't have right nav items, nor a cousin patty */}
                                        <div className="flex flex-col space-y-4">
                                            {rightNavItems.map((item) => (
                                                <a
                                                    key={item.title}
                                                    href={resolveUrl(item.href)}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="flex items-center space-x-2 font-medium"
                                                >
                                                    {item.icon && (
                                                        <Icon
                                                            iconNode={item.icon}
                                                            className="h-5 w-5"
                                                        />
                                                    )}
                                                    <span>{item.title}</span>
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    {/** 
                     * what happens if we move the logo before the mobile nav? 
                     * it moves!
                     */}

                    {/* Desktop Navigation */}
                    <div className="ml-auto hidden h-full w-auto items-stretch justify-stretch xl:space-x-6 justify-items-end justify-self-end lg:flex">
                        <NavigationMenu className="flex h-full items-stretch justify-stretch justify-self-end">
                            <NavigationMenuList className="flex h-full items-stretch space-x-2 justify-items-end">
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
                                                'h-9 cursor-pointer px-3 bg-none font-bold text-lg xl:text-xl',
                                            )}
                                            {...(isSameUrl(page.url, item.href) && {"aria-current": "page"})}
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
                                            <div className="absolute bottom-0.5 left-0 h-0.5 w-full translate-y-px bg-velvet dark:bg-velvet"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>

                    {/* new menu who dis?*/}
                    {/** we don't use right-nav, user info, or breadcrumbs */}
                </div>
            </div>
            {/** we don't use breadcrumbs */}
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
