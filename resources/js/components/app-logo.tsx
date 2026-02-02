import { SharedData } from '@/types';
// import AppLogoIcon from './app-logo-icon';
import HtmlImage from './html-image';
import { usePage } from '@inertiajs/react';
import logoImage from '@img/forest-products-locator_logo@2x.png';


export default function AppLogo() {
    // get application name from page properties
    const { name } = usePage<SharedData>().props;
    const alt = name + ' logo';
    // let lbName = name.split(' ');
    // let zomg = name;
    // if (lbName.length === 4) {
    //     zomg = lbName[0] + ' ' + lbName[1] + '<br>' + lbName[2] + lbName[3];
    // }
    return (
        <>
            <div className="flex aspect-square size-20 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <HtmlImage 
                    src={logoImage}
                    alt={alt}
                    className="size-18 fill-current text-velvet dark:text-velvet" />
            </div>
            <div className="ml-1 grid text-left text-2xl text-wrap lg:max-w-[195px]">
                <span className="mb-0.5 text-wrap leading-tight font-bold font-serif">
                    { name }
                </span>
            </div>
        </>
    );
}
