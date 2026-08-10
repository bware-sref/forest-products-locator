import {
    type GeocodeResult,
    type Mill,
    type SearchParams,
} from '@/types';
import { geocode } from '@/routes/geocoding';

/**
 * Fetch mills from the API.
 *
 * @param url       - The API endpoint URL (passed in from Inertia page props)
 * @param params    - Search/filter parameters to append as query string
 * @param signal    - Optional AbortSignal so the caller can cancel in-flight requests
 * @returns         - Array of Mill objects, or an empty array on error
 */
export async function fetchMills(
    url: string,
    params: SearchParams,
    signal?: AbortSignal,
): Promise<Mill[]> {
    const urlParams = new URLSearchParams(
        // URLSearchParams doesn't accept undefined values, so strip them out first
        Object.fromEntries(
            Object.entries(params).filter(([, v]) => v !== undefined)
        ) as Record<string, string>
    );

    try {
        const response = await fetch(`${url}?${urlParams.toString()}`, { signal });
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }
        return await response.json() as Mill[];
    } catch (error) {
        // Don't log aborted requests — those are intentional
        if (error instanceof Error && error.name !== 'AbortError') {
            console.error(`Error fetching mills: ${error.message}`);
        }
        return [];
    }
}

/**
 * Look up coordinates for a free-form address via GeocodingController::geocode.
 *
 * @param address - Free-form address, city, or zip typed by the user
 * @param signal  - Optional AbortSignal so the caller can cancel in-flight requests
 * @returns       - The best-matching result, or null if none was found or the request failed
 */
export async function geocodeAddress(
    address: string,
    signal?: AbortSignal,
): Promise<GeocodeResult | null> {
    try {
        const response = await fetch(geocode.url({ query: { address } }), { signal });
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }
        const data = await response.json() as { results: GeocodeResult[] | null };
        return data.results?.[0] ?? null;
    } catch (error) {
        if (error instanceof Error && error.name !== 'AbortError') {
            console.error(`Error geocoding address: ${error.message}`);
        }
        return null;
    }
}
