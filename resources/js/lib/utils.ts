import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(
    url1: NonNullable<InertiaLinkProps['href']>,
    url2: NonNullable<InertiaLinkProps['href']>,
) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function isChildUrl(
    childUrl: NonNullable<InertiaLinkProps['href']>,
    parentUrl: NonNullable<InertiaLinkProps['href']>,
) {
    return !isSameUrl(childUrl, parentUrl) && resolveUrl(childUrl).startsWith(resolveUrl(parentUrl));
}

export function isExternalUrl(
    url: NonNullable<InertiaLinkProps['href']>,
    siteUrl: NonNullable<InertiaLinkProps['href']>,
) {
    console.log('unresolved url: ', url);
    const rUrl = resolveUrl(url);
    let parsedUrl;
    // We can identify relative links by trying to instantiate a new URL() with the URL we want to test.
    // Incomplete or malformed URLs cause a TypeError exception.
    try {
        parsedUrl = new URL(rUrl);
        // if parsing the url causes an exception, it's not an absolute URL
    } catch (e) {
        console.error(`exception when parsing URL: '${rUrl}': `, e);
        return false;
    }
    const parsedSiteUrl = new URL(resolveUrl(siteUrl));
    return parsedUrl.hostname !== parsedSiteUrl.hostname;
}