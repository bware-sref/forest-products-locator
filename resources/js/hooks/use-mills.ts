import {
    type ChangeEvent,
    type MouseEventHandler,
    useCallback,
    useEffect,
    useEffectEvent,
    useMemo,
    useState,
} from 'react';
import { debounce } from 'lodash-es';
import {
    type County,
    type Mill,
    type MillType,
    type SearchParams,
    type State,
    type WoodSpecies,
} from '@/types';
import { fetchMills } from '@/lib/api';

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
        abbreviation: state.abbreviation,
        // eslint complained about using String on the left-hand side of ?? which causes the left-hand side to always be a string and thus never nullish. But we need to ensure the value is a string for the select component, so we have to use String() here.
        value: state.value ?? String(state.id),
        label: state.label ?? state.name,
    }));
}

async function downloadExport(params: SearchParams, token: string) {
    console.log('downloadExport with params?', params);
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
    // wrap with useEffectEvent to allow updating isDownloading during useEffect
    const updateIsDownloading = useEffectEvent((downloadingValue: boolean) => {
        setIsDownloading(downloadingValue);
    });

    // ---------------------------------------------------------------------------
    // Mills data
    // ---------------------------------------------------------------------------

    const [mills, setMills] = useState<Mill[]>([]);

    useEffect(() => {
        // AbortController lets us cancel the previous request if searchParams
        // changes before the fetch resolves, replacing the old `ignore` flag.
        const controller = new AbortController();

        // indicate we're loading
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

    /**
     * I think we need useEffect to deal with downloading the export
     */
    // useEffect(() => )


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
        updateIsDownloading(true);
        downloadExport(searchParams, csrfToken)
            .then(() => updateIsDownloading(false));

    }, [searchParams]);

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
    };
}
