import { type Mill, type SearchParams } from '@/types';

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
