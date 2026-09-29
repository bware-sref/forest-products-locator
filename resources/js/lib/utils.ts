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
    const rUrl = resolveUrl(url);
    let parsedUrl;
    // We can identify relative links by trying to instantiate a new URL() with the URL we want to test.
    // Incomplete or malformed URLs cause a TypeError exception.
    try {
        parsedUrl = new URL(rUrl);
        // if parsing the url causes an exception, it's not an absolute URL
    } catch {
        // removing console.error output because it's not technically an error
        // removing the catch parameter altogether because modern JS allows that and lint complains that we defined the exception without using it
        return false;
    }
    const parsedSiteUrl = new URL(resolveUrl(siteUrl));
    return parsedUrl.hostname !== parsedSiteUrl.hostname;
}

export function splitOnNumber(text : string = '') {
    // matches negatives and floats with a non-capture group for the mentissa
    const numberRegEx = new RegExp(/(-?\d+(?:,\d{3})*(?:\.\d+)?)/);
    const parts = text.split(numberRegEx);
    if (parts.length < 3) {
        // that's not right...
        return {
            start: text,
            number: undefined,
            end: undefined,
        };
    }
    return {
        start: parts[0],
        number: parts[1].replaceAll(',', ''),
        end: parts[2],
    };
}

// Returns the number of places on the right-hand side of the decimal point, or 0 if there isn't a decimal point.
export const countDecimals = (num : number) => {
  const numStr = num.toString();
  return numStr.includes('.') ? numStr.split('.')[1].length : 0;
}

