import { useSyncExternalStore, type ReactNode } from "react";

const subscribe = () => () => {};

/**
 * True once hydrated in the browser; false during SSR and on the first
 * client render, so server and client markup match on hydration.
 */
function useHasMounted() {
    return useSyncExternalStore(
        subscribe,
        () => true,
        () => false,
    );
}

interface ClientOnlyProps {
    children: ReactNode;
    fallback?: ReactNode;
}

/**
 * Renders `fallback` (default: nothing) until hydrated in the browser, then
 * swaps to `children`. The server snapshot always reports "not mounted", so
 * SSR renders `fallback` — use this to keep browser-only dependencies (e.g.
 * Leaflet, which touches `window`/`document` on import) out of the server
 * render entirely. Pair with `React.lazy()` for the wrapped component so its
 * module isn't even fetched server-side.
 */
export function ClientOnly({ children, fallback = null }: ClientOnlyProps) {
    const hasMounted = useHasMounted();

    return hasMounted ? children : fallback;
}
