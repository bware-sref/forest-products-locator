//import { NavigationMenu } from "@/components/ui/navigation-menu";
import { dashboard, login, register, millMap, millList, aboutUs, home } from '@/routes';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function TopNav({
    canRegister = true,
    pageTitle = '',
}: {
    canRegister?: boolean;
    pageTitle?: string;
}) {
    const page = usePage<SharedData>();
    // const { auth } = usePage<SharedData>().props;
    const { auth } = page.props;

    return (
        <>
            <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl flex flex-col-reverse">
                {pageTitle ? (
                    <h1 className="text-lg pt-3 pb-1.5 leading-normal text-[#1b1b1b] dark:text-[#EDEDEC]">{pageTitle}</h1>
                ) : (
                    ''
                )}
                
                <nav className="flex items-center justify-end gap-4">
                    <Link
                        href={home()}
                        className="inline-block px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:text-[#1915] dark:text-[#EDEDEC] dark:hover:text-[#62605b]"
                    >
                        {page.props.name}
                    </Link>

                    <Link
                        href={millMap()}
                        className="inline-block px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:text-[#1915] dark:text-[#EDEDEC] dark:hover:text-[#62605b]"
                    >
                        Mill Map
                    </Link>
                    <Link
                        href={millList()}
                        className="inline-block px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:text-[#1915] dark:text-[#EDEDEC] dark:hover:text-[#62605b]"
                    >
                        Mill List
                    </Link>
                    <Link
                        href={aboutUs()}
                        className="inline-block px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:text-[#1915] dark:text-[#EDEDEC] dark:hover:text-[#62605b]"
                    >
                        About Us
                    </Link>
                    {/** auth stuff probably isn't needed in top nav */}
                    {auth.user ? (
                        <Link
                            href={dashboard()}
                            className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                            >
                                Log in
                            </Link>
                            {canRegister && (
                                <Link
                                    href={register()}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Register
                                </Link>
                            )}
                        </>
                    )}                    
                </nav>
            </header>
        </>
    );
}