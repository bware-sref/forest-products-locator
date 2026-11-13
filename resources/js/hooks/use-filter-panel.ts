import { useCallback, useState, useSyncExternalStore } from 'react';

/**
 * Open/closed state for the mill filters panel.
 *
 * Desktop (lg and up): an inline panel that leaves the page usable. Starts open, and remembers whether the visitor
 * closed it for the rest of the browser session (sessionStorage), so it stays put when moving between Mill Map and
 * Mill List.
 * Smaller screens: a modal Sheet. Always starts closed and isn't remembered.
 *
 * Both values come from useSyncExternalStore with a server snapshot, so the SSR markup (desktop, open) hydrates
 * cleanly and the stored/actual values are applied right after.
 */

/** Tailwind's lg breakpoint: the filters' lg: styles are the desktop layout. */
const DESKTOP_QUERY = '(min-width: 1024px)';
const STORAGE_KEY = 'fpn:mill-filters-panel-open';
/** sessionStorage doesn't fire 'storage' events in the tab that wrote it, so we dispatch our own. */
const CHANGE_EVENT = 'fpn:mill-filters-panel-change';

function subscribeToDesktop(callback: () => void): () => void {
    const mql = window.matchMedia(DESKTOP_QUERY);
    mql.addEventListener('change', callback);
    return () => mql.removeEventListener('change', callback);
}

export function useIsDesktop(): boolean {
    return useSyncExternalStore(
        subscribeToDesktop,
        () => window.matchMedia(DESKTOP_QUERY).matches,
        () => true,
    );
}

function subscribeToStoredOpen(callback: () => void): () => void {
    window.addEventListener(CHANGE_EVENT, callback);
    window.addEventListener('storage', callback);
    return () => {
        window.removeEventListener(CHANGE_EVENT, callback);
        window.removeEventListener('storage', callback);
    };
}

function readStoredOpen(): boolean {
    try {
        return window.sessionStorage.getItem(STORAGE_KEY) !== 'false';
    } catch {
        // storage can be blocked (privacy settings); fall back to the default
        return true;
    }
}

function writeStoredOpen(open: boolean): void {
    try {
        window.sessionStorage.setItem(STORAGE_KEY, String(open));
    } catch {
        // not remembered, but still works for this page
    }
    window.dispatchEvent(new Event(CHANGE_EVENT));
}

export interface UseFilterPanelReturn {
    isDesktop: boolean;
    open: boolean;
    setOpen: (open: boolean) => void;
    toggle: () => void;
}

export function useFilterPanel(): UseFilterPanelReturn {
    const isDesktop = useIsDesktop();
    const desktopOpen = useSyncExternalStore(subscribeToStoredOpen, readStoredOpen, () => true);
    const [mobileOpen, setMobileOpen] = useState(false);

    const open = isDesktop ? desktopOpen : mobileOpen;

    const setOpen = useCallback((next: boolean) => {
        if (isDesktop) {
            writeStoredOpen(next);
        } else {
            setMobileOpen(next);
        }
    }, [isDesktop]);

    const toggle = useCallback(() => setOpen(!open), [setOpen, open]);

    return { isDesktop, open, setOpen, toggle };
}
