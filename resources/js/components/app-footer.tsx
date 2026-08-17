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
import {
    useIsMobile
} from '@/hooks/use-mobile';


/**
 * Externalized as sgsfNav
 */

const activeItemStyles =
    'text-velvet-900 dark:bg-velvet-800 dark:text-velvet-100';

const secondaryActiveItemStyles = 'text-beluga';

export function AppFooter() {
    const page = usePage<SharedData>();

    // so we can juggle values for the data-orientation property on nav elements
    const isMobile = useIsMobile();    

    return (
        <>
            <div className="bg-nature text-beluga my-8 w-full md:border-t-beluga md:border-t-4 md:mt-0 md:pt-8">
                <div className="mx-auto flex flex-col px-5 md:flex-row lg:h-20 items-center md:max-w-6xl lg:max-w-7xl ">
                    {/* Main Navigation + SGSF link */}
                    <div className="h-full items-stretch md:items-center space-6 flex flex-col lg:flex-row w-full">
                        <NavigationMenu 
                            data-orientation={isMobile ? 'vertical' : 'horizontal'}
                            className="flex flex-col md:flex-row justify-stretch h-full items-stretch lg:-ml-4 w-full md:w-auto max-w-full pb-5 lg:pb-0"
                        >
                            <NavigationMenuList
                                data-orientation={isMobile ? 'vertical' : 'horizontal'}
                                className="flex flex-col md:flex-row h-full items-center justify-items-start md:items-stretch md:space-x-2 md:justify-items-end"
                            >
                                {primaryNavItems.map((item, index) => (
                                    <NavigationMenuItem
                                        key={index}
                                        className="relative flex h-full items-center"
                                    >
                                        <Link
                                            viewTransition
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                isSameUrl(
                                                    page.url,
                                                    item.href,
                                                ) && activeItemStyles,
                                                'h-9 cursor-pointer px-3 bg-none font-bold text-xl md:text-lg lg:text-xl text-white',
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
                                            <div className="absolute bottom-0.5 left-0 h-0.5 w-full translate-y-px bg-white dark:bg-white"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                        {/**
                         * SGSF logo & link
                         * 
                         * Actually, I'm not sure if we can use Link for this because it's an external URL.
                         */}
                        <div className="mx-auto md:ml-auto flex max-w-42 justify-self-end mt-6 md:mt-0">
                            <Link
                                viewTransition                                
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
                    <NavigationMenu
                        aria-label="Secondary"
                        data-orientation={isMobile ? 'vertical' : 'horizontal'}
                        className="flex flex-col md:flex-row h-full items-stretch w-full md:w-auto max-w-full py-2  justify-end md:-mr-4"
                    >
                        <NavigationMenuList
                            data-orientation={isMobile ? 'vertical' : 'horizonta'}
                            className="flex flex-col md:flex-row h-full items-center justify-items-end md:items-stretch md:space-x-2 md:justify-items-end"
                        >
                            {/* 
                            FFS, Beluga has insufficient contrast with Nature when font-size < 20px!!!
                            Fix: use white instead of Beluga!
                            */}
                            {secondaryNavItems.map((item, index) => (
                                <NavigationMenuItem
                                    key={index}
                                    className="relative flex h-full items-center"
                                >                                        
                                    {! isExternalUrl(item.href, page.props.seo.siteUrl) ? (
                                        <Link
                                            viewTransition
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                isSameUrl(
                                                    page.url,
                                                    item.href,
                                                ) && secondaryActiveItemStyles,
                                                'h-9 cursor-pointer px-3 bg-none font-bold text-md text-white',
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
                                                'h-9 cursor-pointer px-3 bg-none font-bold text-md text-white',
                                            )}
                                            rel="nofollow noreferrer noopener"
                                        >
                                            {item.title}
                                        </a>
                                    )}
                                    {isSameUrl(page.url, item.href) && (
                                        <div className="absolute bottom-0.5 left-0 h-0.5 w-full translate-y-px bg-white dark:bg-white"></div>
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
