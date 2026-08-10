import {
    type ChangeEvent,
    type MouseEventHandler,
    useCallback,
    useEffect,
    useEffectEvent,
    useMemo,
    useRef,
    useState,
} from 'react';
import { debounce } from 'lodash-es';
import {
    type County,
    type GeocodeResult,
    type GeolocationStatus,
    type Mill,
    type MillType,
    type SearchParams,
    type State,
    type WoodSpecies,
} from '@/types';
import {
    fetchMills,
    geocodeAddress,
} from '@/lib/api';

/**
 * Maps state IDs to their county lists.
 * Keyed by state.id (as a string, since object keys are always strings).
 */
export type CountiesByState = Record<string, County[]>;

/**
 * Builds a lookup table of counties keyed by state ID from the full states list.
 * Defined outside the hook so it isn't recreated on every render.
 */
export function buildCountiesByState(states: State[]): CountiesByState {
    const result: CountiesByState = {};
    for (const state of states) {
        if (state.counties !== undefined) {
            result[state.id] = state.counties;
        }
    }
    return result;
}

/**
 * Strips county-stripped State objects suitable for the filter dropdowns.
 * Defined outside the hook so it isn't recreated on every render.
 */
export function normalizeStates(states: State[]): State[] {
    return states.map((state) => ({
        id: state.id,
        name: state.name,
        slug: state.slug,
        abbreviation: state.abbreviation,
        // eslint complained about using String on the left-hand side of ?? which causes the left-hand side to always be a string and thus never nullish. But we need to ensure the value is a string for the select component, so we have to use String() here.
        value: state.value ?? String(state.id),
        label: state.label ?? state.name,
    }));
}

/**
 * Builds a short display string for a geocode result — street, city, state,
 * and a 5-digit zip — omitting country and any ZIP+4 suffix. The API's own
 * `label` is the full normalized address (country included), which is more
 * detail than MillFilters wants to show once a custom location is active.
 * Defined outside the hook so it isn't recreated on every render.
 */
export function formatLocationLabel(result: GeocodeResult): string {
    const zip = result.zip?.split('-')[0];
    const cityStateZip = [result.city, [result.state_code, zip].filter(Boolean).join(' ')]
        .filter(Boolean)
        .join(', ');
    return [result.street_address, cityStateZip].filter(Boolean).join(', ');
}

async function downloadExport(params: SearchParams, token: string) {
    // console.log('downloadExport with params?', params);
    const response = await fetch("/mills/export", {
        method: "POST",
        headers: {
            // "Content-type": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-type": "application/json",
            "X-CSRF-TOKEN": token,
        },
        body: JSON.stringify(params) as BodyInit,
    });
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "mills.xlsx";
    a.click();
}

export interface UseMillsProps {
    /** The mills API endpoint URL, passed down from Inertia page props */
    millsApiUrl: string;
    /** Full states list including nested counties, from Inertia page props */
    rawStates: State[];
    /** Mill types for the filter dropdown, from Inertia page props */
    millTypes?: MillType[];
    /** Wood species for the filter dropdown, from Inertia page props */
    woodSpecies?: WoodSpecies[];
    /** CSRF token */
    csrfToken: string;
}

export interface UseMillsReturn {
    // --- Data ---
    mills: Mill[];
    /** County list for the county dropdown; populated once a state is selected */
    counties: County[];
    /**
     * State objects stripped of their nested county lists, ready to pass
     * straight into the MillFilters component.
     */
    states: State[];

    // --- Current filter values (for controlled inputs in MillFilters) ---
    searchText: string;
    searchParams: SearchParams;
    /**
     * Increments each time filters are cleared. Pass as the `key` prop on each
     * InputSelect in MillFilters so React remounts them, wiping their internal
     * selected-value state and resetting the displayed label to the placeholder.
     */
    filterResetKey: number;

    /**
     * monitor API loading state
     */
    isLoading: boolean;

    /**
     * monitor downloading state
     */
    isDownloading: boolean;

    // --- Event handlers to wire directly to MillFilters props ---
    handleTextSearchChange: (event: ChangeEvent<HTMLInputElement>) => void;
    handleStateSelectChange: (optionValue: string) => void;
    handleCountySelectChange: (countyId: string) => void;
    handleMillTypeSelectChange: (millTypeId: string) => void;
    handleWoodSpeciesSelectChange: (woodSpeciesId: string) => void;
    handleClearFiltersClick: MouseEventHandler<HTMLButtonElement>;
    handleExportClick: MouseEventHandler<HTMLButtonElement>;

    // --- Proximity / geolocation ---
    /**
     * Status of the browser Geolocation API's own permission/request cycle
     * only — never repurposed to mean "a location is active" (see
     * isLocationActive for that). Drives the "Use my location" button's
     * disabled state and the radius dropdown's tooltip explanation.
     */
    geolocationStatus: GeolocationStatus;
    /**
     * Handler for the "Use my location" button in MillFilters
     */
    handleRequestLocationClick: MouseEventHandler<HTMLButtonElement>;
    /**
     * Handler for the radius InputSelect
     */
    handleRadiusSelectChange: (radius: string) => void;

    coordinates: {lat: number, lng: number} | null;

    radius: string | null;

    /**
     * Handler for the custom location form's submit in MillFilters. Geocodes
     * the given address and, on success, sets coordinates and a display
     * label without touching geolocationStatus.
     */
    handleCustomLocationSubmit: (address: string) => void;
    /**
     * Whether a usable location (from either source) is currently active.
     * Drives the radius dropdown and MillFilters' "location is set" summary.
     */
    isLocationActive: boolean;
    /**
     * True while a custom location lookup is in flight.
     */
    isGeocoding: boolean;
    /**
     * Set when the most recent custom location lookup failed to resolve to
     * a usable address. Cleared as soon as another lookup starts.
     */
    geocodeError: string | null;
    /**
     * The normalized address label for the active custom location, if any.
     * Null when the active location came from browser geolocation instead.
     */
    locationLabel: string | null;
    /**
     * Handler for the "reset location" link in MillFilters. Clears
     * coordinates, radius, and geolocation/custom-location state so the
     * filters revert to showing the "Use my location" / "Choose location"
     * buttons.
     */
    handleResetLocationClick: MouseEventHandler<HTMLButtonElement>;
}

/**
 * Encapsulates all mill-related state, filter handlers, and API fetching so
 * that both MillListPage and MillMapPage can share identical behaviour without
 * duplicating any logic.
 *
 * Usage:
 * ```tsx
 * const { mills, states, counties, searchText, searchParams, ...handlers } = useMills({
 *     millsApiUrl: page.props.millsApiUrl,
 *     rawStates: page.props.states,
 *     millTypes: page.props.millTypes,
 *     woodSpecies: page.props.woodSpecies,
 * });
 * ```
 */
export function useMills({
    millsApiUrl,
    rawStates,
    millTypes,
    woodSpecies,
    csrfToken,
}: UseMillsProps): UseMillsReturn {

    // ---------------------------------------------------------------------------
    // Derived-but-stable data (safe to compute once per prop change via useMemo)
    // ---------------------------------------------------------------------------

    const states = useMemo(() => normalizeStates(rawStates), [rawStates]);
    const countiesByState = useMemo(() => buildCountiesByState(rawStates), [rawStates]);

    // ---------------------------------------------------------------------------
    // State
    // ---------------------------------------------------------------------------

    /** The raw string value of the text search input (drives the controlled input) */
    const [searchText, setSearchText] = useState<string>('');

    /**
     * County list for the county dropdown.
     * UI-only — not part of searchParams — so it stays as its own state variable.
     */
    const [counties, setCounties] = useState<County[]>([]);

    /**
     * Incremented by handleClearFiltersClick. Passed to MillFilters as a prop
     * and applied as the `key` on each InputSelect, causing React to remount
     * them and reset their internal selectedValue state to the placeholder.
     */
    const [filterResetKey, setFilterResetKey] = useState<number>(0);

    /**
     * Single source of truth for all active filter values.
     * Watching this in the fetch useEffect replaces the previous pattern of
     * maintaining separate selectedState/selectedCounty/etc. variables.
     */
    const [searchParams, setSearchParams] = useState<SearchParams>({});

    /**
     * add isLoading state variable to make API loading state visible to other components
     */
    const [isLoading, setIsLoading] = useState<boolean>(false);
    // make useEffectEvent wrapper to allow updating isLoading during useEffect
    const updateIsLoading = useEffectEvent((loadingValue: boolean) => {
        setIsLoading(loadingValue);
    });

    /**
     * isDownloading state management
     */
    const [isDownloading, setIsDownloading] = useState<boolean>(false);

    // ---------------------------------------------------------------------------
    // Mills data
    // ---------------------------------------------------------------------------

    const [mills, setMills] = useState<Mill[]>([]);

    useEffect(() => {
        // AbortController lets us cancel the previous request if searchParams
        // changes before the fetch resolves, replacing the old `ignore` flag.
        const controller = new AbortController();

        // indicate we're loading
        // this allegedly the correct approach so we're just going to suppress the alleged error
        // eslint-disable-next-line
        updateIsLoading(true);

        fetchMills(millsApiUrl, searchParams, controller.signal).then((result) => {
            setMills(result);
        }).finally(() => {
            setIsLoading(false);
        });

        return () => {
            controller.abort();
            setIsLoading(false);
        };
    }, [millsApiUrl, searchParams]);


    // ---------------------------------------------------------------------------
    // Text search — debounced so we don't fire on every keystroke
    // ---------------------------------------------------------------------------

    /**
     * The inner callback that actually updates searchParams.
     * Wrapped in useCallback so the debounce reference stays stable.
     */
    const applyTextSearch = useCallback((value: string) => {
        setSearchParams((prev) => {
            const next = { ...prev };
            if (value) {
                next.q = value;
            } else {
                delete next.q;
            }
            return next;
        });
    }, []);

    const debouncedTextSearch = useMemo(
        () => debounce((value: string) => applyTextSearch(value), 500),
        [applyTextSearch],
    );

    const handleTextSearchChange = useCallback((event: ChangeEvent<HTMLInputElement>) => {
        const value = event.target.value;
        setSearchText(value);
        debouncedTextSearch(value);
    }, [debouncedTextSearch]);

    // ---------------------------------------------------------------------------
    // Select handlers
    // ---------------------------------------------------------------------------

    const handleStateSelectChange = useCallback((optionValue: string) => {
        if (optionValue === '') {
            // Clearing state also clears the county dropdown and removes both
            // params so the API isn't called with a stale county value.
            setCounties([]);
            setSearchParams((prev) => {
                const next = { ...prev };
                delete next.state;
                delete next.county;
                return next;
            });
            return;
        }

        setCounties(countiesByState[optionValue] ?? []);
        setSearchParams((prev) => ({
            ...prev,
            state: optionValue,
            // Changing state should also clear any previously selected county
            // so the county dropdown resets to a consistent empty state.
            ...(prev.county ? { county: undefined } : {}),
        }));
    }, [countiesByState]);

    const handleCountySelectChange = useCallback((countyId: string) => {
        setSearchParams((prev) => {
            const next = { ...prev };
            if (countyId === '') {
                delete next.county;
            } else {
                next.county = countyId;
            }
            return next;
        });
    }, []);

    const handleMillTypeSelectChange = useCallback((millTypeId: string) => {
        // Confirm the ID resolves to a known MillType before updating params.
        // This guards against stale or spoofed values from the dropdown.
        const resolved = millTypes?.find((mt) => mt.id === parseInt(millTypeId));

        setSearchParams((prev) => {
            const next = { ...prev };
            if (millTypeId === '' || !resolved) {
                delete next.millType;
            } else {
                next.millType = millTypeId;
            }
            return next;
        });
    }, [millTypes]);

    const handleWoodSpeciesSelectChange = useCallback((woodSpeciesId: string) => {
        const resolved = woodSpecies?.find((w) => w.id === parseInt(woodSpeciesId));

        setSearchParams((prev) => {
            const next = { ...prev };
            if (woodSpeciesId === '' || !resolved) {
                delete next.woodSpecies;
            } else {
                next.woodSpecies = woodSpeciesId;
            }
            return next;
        });
    }, [woodSpecies]);

    const handleClearFiltersClick: MouseEventHandler<HTMLButtonElement> = useCallback(() => {
        setSearchText('');
        setCounties([]);
        setFilterResetKey((k) => k + 1);
        // Resetting to an empty object triggers the fetch useEffect, which will
        // re-fetch the unfiltered mill list — fixing the previous bug where
        // clearing filters didn't actually refresh the data.
        setSearchParams({});
    }, []);

    /**
     * Need to add another method for handling Export clicks
     */
    const handleExportClick: MouseEventHandler<HTMLButtonElement> = useCallback(() => {
        // console.log('clicked Export with following searchParams!', searchParams);
        // attach CSRF token to searchParams
        console.log('doing the download with searchParams...', searchParams);
        setIsDownloading(true);
        downloadExport(searchParams, csrfToken)
            .then(() => setIsDownloading(false));

    }, [searchParams, csrfToken]);

    // ---------------------------------------------------------------------------
    // Geolocation + proximity filter
    // ---------------------------------------------------------------------------

    /**
     * Coordinates are stored in a ref rather than state because they don't need
     * to trigger a re-render on their own - only when combined with a radius
     * selection should a fetch fire.  The ref gives the handlers stable, synchronous
     * access to the latest coordinates without stale-closure risk.
     * 
     * Probably need to update this to be useState so we can use it to add a map pin and circle...
     */
    const coordinatesRef = useRef<{ lat: number; lng: number } | null>(null);
    // this probably obviates coordinatesRef
    const [coordinates, setCoordinates] = useState<{ lat: number; lng: number } | null>(null);
    const [radius, setRadius] = useState<string | null>(null);

    const [geolocationStatus, setGeolocationStatus] = useState<GeolocationStatus>('idle');

    /**
     * On mount, silently check whether the browser already has a stored
     * decision for this origin's geolocation permission — the Permissions
     * API lets us read this without triggering a prompt, unlike
     * getCurrentPosition(). This only ever seeds geolocationStatus (so e.g.
     * "Use my location" isn't shown disabled) and, if already granted,
     * fires a throwaway getCurrentPosition() call purely to warm up the
     * device's location subsystem for a snappier response later.
     *
     * This deliberately never sets coordinates/isLocationActive — MillFilters
     * must always start on its two location buttons, and the user must
     * always click "Use my location" to actually apply a position, even on
     * a repeat visit where permission was already granted.
     */
    useEffect(() => {
        if (!navigator.permissions?.query) {
            return;
        }

        let cancelled = false;

        navigator.permissions.query({ name: 'geolocation' }).then((status) => {
            if (cancelled) return;

            if (status.state === 'granted') {
                setGeolocationStatus('granted');
                // Result intentionally discarded — see comment above.
                navigator.geolocation.getCurrentPosition(() => {}, () => {});
            } else if (status.state === 'denied') {
                setGeolocationStatus('denied');
            }
            // 'prompt' means no decision yet — leave geolocationStatus at 'idle'.
        });

        return () => {
            cancelled = true;
        };
    }, []);

    /**
     * Custom (typed-address) location lookup state. geolocationStatus tracks
     * only the browser Geolocation API's own permission/request outcome, so
     * a successful custom-address lookup never touches it — whether a
     * location is active at all (for the radius dropdown and MillFilters'
     * "location is set" UI) is instead read off `coordinates` directly, via
     * `isLocationActive` below.
     */
    const [isGeocoding, setIsGeocoding] = useState<boolean>(false);
    const [geocodeError, setGeocodeError] = useState<string | null>(null);
    const [locationLabel, setLocationLabel] = useState<string | null>(null);

    const handleRequestLocationClick: MouseEventHandler<HTMLButtonElement> = useCallback(() => {
        if (! navigator.geolocation) {
            setGeolocationStatus('unavailable');
            return;
        }

        setGeolocationStatus('requesting');
        
        navigator.geolocation.getCurrentPosition(
            // success
            (position) => {
                coordinatesRef.current = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                setCoordinates(coordinatesRef.current);
                setGeolocationStatus('granted');
                // No address label for browser geolocation — MillFilters falls
                // back to a generic "location is set" message when this is null.
                setLocationLabel(null);
                setGeocodeError(null);
                // Don't fire a proximity fetch yet, the user still needs to pick a radius.
                // Coordinates are ready and waiting in the ref.
            },
            // error
            (error) => {
                coordinatesRef.current = null;
                setCoordinates(null);
                if (error.code === GeolocationPositionError.PERMISSION_DENIED) {
                    setGeolocationStatus('denied');
                } else {
                    // POSITION_UNAVAILABLE or TIMEOUT both fall here
                    setGeolocationStatus('unavailable');
                }
                // Remove any stale proximity params so the fetch stays clean
                setSearchParams((prev) => {
                    const next = { ...prev };
                    delete next.lat;
                    delete next.lng;
                    delete next.radius;
                    return next;
                });
            },
            // options?
        );

    }, []);

    const handleRadiusSelectChange = useCallback((radius: string) => {
        setSearchParams((prev) => {
            const next = { ...prev };
            if (radius === '' || !coordinatesRef.current) {
                // Clearing the radius doesn't need to remove all three proximity parameters, only radius
                // delete next.lat;
                // delete next.lng;
                delete next.radius;
                setRadius(null);
            } else {
                // All three properties are set atomically so the API never receives a 
                // partial proximity query
                next.lat = String(coordinatesRef.current.lat);
                next.lng = String(coordinatesRef.current.lng);
                next.radius = radius;
                setRadius(radius);
            }
            return next;
        });
    }, []);

    /**
     * Geocodes a free-form address typed into MillFilters' custom location
     * form. On success this sets coordinates and a display label, the same
     * inputs a granted browser geolocation request would produce — but
     * doesn't touch geolocationStatus, since that's specifically about the
     * browser's own permission state, which this path never interacts with.
     */
    const handleCustomLocationSubmit = useCallback((address: string) => {
        setIsGeocoding(true);
        setGeocodeError(null);

        geocodeAddress(address).then((result) => {
            if (!result || result.latitude === null || result.longitude === null) {
                setGeocodeError("We couldn't find that location. Try a different address.");
                return;
            }

            coordinatesRef.current = { lat: result.latitude, lng: result.longitude };
            setCoordinates(coordinatesRef.current);
            setLocationLabel(formatLocationLabel(result) || address);
        }).finally(() => {
            setIsGeocoding(false);
        });
    }, []);

    /**
     * Clears whichever location source is active (browser geolocation or
     * custom address) so MillFilters reverts to its two initial buttons and
     * the map marker/circle disappear. Doesn't touch geolocationStatus —
     * the browser's actual permission state doesn't change just because
     * we've cleared our own coordinates.
     */
    const handleResetLocationClick: MouseEventHandler<HTMLButtonElement> = useCallback(() => {
        coordinatesRef.current = null;
        setCoordinates(null);
        setRadius(null);
        setLocationLabel(null);
        setGeocodeError(null);
        setSearchParams((prev) => {
            const next = { ...prev };
            delete next.lat;
            delete next.lng;
            delete next.radius;
            return next;
        });
    }, []);

    /**
     * Whether a usable location is active, regardless of source. This — not
     * geolocationStatus — is what gates the radius dropdown and MillFilters'
     * "location is set" summary, since a custom address can produce
     * coordinates without ever touching the browser's geolocation API.
     */
    const isLocationActive = coordinates !== null;

    // ---------------------------------------------------------------------------

    return {
        mills,
        counties,
        states,
        searchText,
        searchParams,
        filterResetKey,
        isLoading,
        isDownloading,
        handleTextSearchChange,
        handleStateSelectChange,
        handleCountySelectChange,
        handleMillTypeSelectChange,
        handleWoodSpeciesSelectChange,
        handleClearFiltersClick,
        handleExportClick,
        geolocationStatus,
        handleRequestLocationClick,
        handleRadiusSelectChange,
        coordinates,
        radius,
        handleCustomLocationSubmit,
        isGeocoding,
        geocodeError,
        locationLabel,
        handleResetLocationClick,
        isLocationActive,
    };
}
