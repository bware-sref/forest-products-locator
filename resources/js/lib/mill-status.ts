/**
 * What screen readers hear when a mill search starts and finishes (WCAG 4.1.3).
 * Shared by the live regions in TitleFilterBar and MillFilters: an open Filters dialog hides
 * everything behind it from screen readers (aria-hidden), so the dialog needs its own region.
 * Switching to "Loading" in between means a repeated count is still announced.
 */
export function millStatusMessage({
    isLoading,
    loadError = false,
    millCount = 0,
}: {
    isLoading: boolean;
    loadError?: boolean;
    millCount?: number;
}): string {
    if (isLoading) return 'Loading mills…';
    if (loadError) return "Couldn't load mills.";
    return millCountText(millCount);
}

/** "1 mill found." / "12 mills found." */
export function millCountText(millCount = 0): string {
    return millCount === 1 ? '1 mill found.' : `${millCount} mills found.`;
}
