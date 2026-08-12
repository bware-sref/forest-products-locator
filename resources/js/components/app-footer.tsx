import { Icon } from '@/components/icon';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    cn,
    isSameUrl,
    isExternalUrl,
} from '@/lib/utils';
import {
    // type NavItem,
    type SharedData
} from '@/types';
import { Link, usePage } from '@inertiajs/react';
import sgsfLogo from '@img/southern-group-of-state-foresters_logo_white_horizontal@2x.png';
import {
    primaryNavItems,
    sgsfNav as sGSF,
    secondaryNavItems,
} from '@/config/navigation';

/**
 * Externalized as sgsfNav
 */

const activeItemStyles =
    'text-velvet-900 dark:bg-velvet-800 dark:text-velvet-100';

const secondaryActiveItemStyles = 'text-beluga';

export function AppFooter() {
    const page = usePage<SharedData>();

    return (
        <>
            <div className="bg-nature text-beluga my-8 w-full md:border-t-beluga md:border-t-4 md:mt-0 md:pt-8">
                <div className="mx-auto flex flex-col px-5 md:flex-row lg:h-20 items-center md:max-w-6xl lg:max-w-7xl ">
                    {/* Main Navigation + SGSF link */}
                    <div className="h-full items-stretch md:items-center space-6 flex flex-col lg:flex-row w-full">
                        <NavigationMenu className="flex flex-col md:flex-row justify-stretch h-full items-stretch lg:-ml-4 w-full md:w-auto max-w-full pb-5 lg:pb-0">
                            <NavigationMenuList className="flex flex-col md:flex-row h-full items-center justify-items-start md:items-stretch md:space-x-2 md:justify-items-end">
                                {primaryNavItems.map((item, index) => (
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
                                                'h-9 cursor-pointer px-3 bg-none font-bold text-xl md:text-lg lg:text-xl',
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
                                            <div className="absolute bottom-0.5 left-0 h-0.5 w-full translate-y-px bg-beluga dark:bg-beluga"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                        {/**
                         * SGSF logo & link
                         */}
                        <div className="mx-auto md:ml-auto flex max-w-42 justify-self-end mt-6 md:mt-0">
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
                {/* Secondary Nav */}
                <div className="mt-4 mx-auto px-5 md:max-w-6xl lg:max-w-7xl h-full items-center md:justify-end space-6 flex flex-row w-full">
                    <NavigationMenu className="flex flex-col md:flex-row h-full items-stretch w-full md:w-auto max-w-full py-2  justify-end md:-mr-4">
                            <NavigationMenuList className="flex flex-col md:flex-row h-full items-center justify-items-end md:items-stretch md:space-x-2 md:justify-items-end">
                                {secondaryNavItems.map((item, index) => (
                                    <NavigationMenuItem
                                        key={index}
                                        className="relative flex h-full items-center"
                                    >                                        
                                        {! isExternalUrl(item.href, page.props.seo.siteUrl) ? (
                                            <Link
                                                href={item.href}
                                                className={cn(
                                                    navigationMenuTriggerStyle(),
                                                    isSameUrl(
                                                        page.url,
                                                        item.href,
                                                    ) && secondaryActiveItemStyles,
                                                    'h-9 cursor-pointer px-3 bg-none font-bold text-md',
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
                                        ) : (
                                            <a 
                                                href={item.href as string}
                                                target="_blank"
                                                className={cn(
                                                    navigationMenuTriggerStyle(),
                                                    isSameUrl(
                                                        page.url,
                                                        item.href,
                                                    ) && secondaryActiveItemStyles,
                                                    'h-9 cursor-pointer px-3 bg-none font-bold text-md',
                                                )}
                                                rel="nofollow noreferrer noopener"
                                            >
                                                {item.title}
                                            </a>
                                        )}
                                        {isSameUrl(page.url, item.href) && (
                                            <div className="absolute bottom-0.5 left-0 h-0.5 w-full translate-y-px bg-beluga dark:bg-beluga"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}                                
                            </NavigationMenuList>
                    </NavigationMenu>
                </div>

            </div>
        </>
    );
}
