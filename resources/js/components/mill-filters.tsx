import { cn } from "@/lib/utils";
import {
    type County,
    type MillType,
    type State,
    type WoodSpecies,
    type SelectOption,
    type SearchParams,
    GeolocationStatus,
} from '@/types';
import {
    Field,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from "@/components/ui/input-group";
import { Button } from '@/components/ui/button';
import {
    LocateFixed,
    Loader2,
    MapPinIcon,
    SearchIcon,
    // SlidersHorizontalIcon,
} from 'lucide-react';
import {
    ChangeEvent,
    MouseEventHandler,
    type SyntheticEvent,
    useState,
} from 'react';
import {
    InputSelect,
    InputSelectTrigger,
} from "@/components/extend/input-select";
import { ClassValue } from "clsx";
// import { useIsMobile } from '@/hooks/use-mobile';
// import { ScrollArea } from "@/components/ui/scroll-area";
// import { useGeolocated } from "react-geolocated";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { millCountText, millStatusMessage } from '@/lib/mill-status';

/**
 * MillFilters needs:
 * - states, which we might want to just pluck with usePage
 * - counties, which come attached to states
 * - millTypes
 * - woodSpecies
 * - callbacks for
 *  - onStateChange
 *  - onCountyChange
 *  - onMillType
 *  - onWoodSpecies
 */
export interface MillFiltersProps {
    headline?: string;
    textSearch?: string;
    states: State[];
    counties?: County[];
    millTypes?: MillType[];
    woodSpecies?: WoodSpecies[];
    /**
     * Probably need props for selected values of each select box.
     */
    // searchParams?: SearchParams;

    onTextSearchChange: (event: ChangeEvent<HTMLInputElement>) => void;
    onStateSelectChange: (stateId: string) => void;
    onCountySelectChange: (countyId: string) => void;
    onMillTypesSelectChange: (millTypeId: string) => void;
    onWoodSpeciesSelectChange: (woodSpeciesId: string) => void;

    // add to that ClearFilters
    onClearFiltersClick: MouseEventHandler;

    // add Export
    onExportClick?: MouseEventHandler;
    /**
     * Incremented by the parent when all filters are cleared. Applied as the
     * `key` prop on each InputSelect so React remounts them, resetting their
     * internal state and restoring the placeholder label.
     */
    filterResetKey?: number;
    isLoading?: boolean;
    isDownloading: boolean;

    /**
     * Add properties for pre-selected values
     * - state
     * - county
     * - mill types
     * - wood species
     * Or, just use searchParams?
     */

    searchParams?: SearchParams;
    className?: ClassValue;

    // --- Proximity / geolocation ---
    /** Status of the browser Geolocation API's own permission/request cycle only. */
    geolocationStatus: GeolocationStatus;
    onRequestLocationClick: MouseEventHandler<HTMLButtonElement>;
    onRadiusSelectChange: (radius: string) => void;
    /**
     * Whether a usable location (from either the browser or a custom
     * address) is currently active. Gates the radius dropdown and the
     * "location is set" summary — independent of geolocationStatus, since a
     * custom address never touches the browser's permission state.
     */
    isLocationActive: boolean;

    // --- Custom (typed-address) location ---
    /** Submits the typed address to the geocoding endpoint. */
    onCustomLocationSubmit: (address: string) => void;
    /** Clears whichever location (geolocation or custom) is currently active. */
    onResetLocationClick: MouseEventHandler<HTMLButtonElement>;
    /** True while a custom location lookup is in flight. */
    isGeocoding?: boolean;
    /** Set when the most recent custom location lookup failed. */
    geocodeError?: string | null;
    /** Normalized address for the active custom location; null for browser geolocation. */
    locationLabel?: string | null;

    // add a mill count
    millCount?: number;
    /** the last mill search failed (as opposed to finding nothing) */
    loadError?: boolean;

    /**
     * Closes the mobile filters Sheet. When given, a "Show Results" button appears next to Clear Filters once the
     * filters find mills. Leave it out on desktop, where the results are already visible.
     */
    onShowResultsClick?: () => void;
    /**
     * When given, screen readers get this as the panel's heading instead of `headline`, and the visible headline
     * is hidden from them. On desktop the title bar's <h1> already says "Mill Map" or "Mill List", so repeating it
     * as this heading would be a duplicate that doesn't describe the filters.
     */
    screenReaderHeading?: string;
    /**
     * Announce search results from inside the filters. Needed only when the filters are a modal (mobile Sheet),
     * which hides the title bar's announcement from screen readers; on desktop it would be read twice.
     */
    announceStatus?: boolean;
}

/**
 * Preset raidus options in miles.
 * Fine-grainded at short haul distances, then wider steps beyond 25 miles.
 */
const RADIUS_OPTIONS: SelectOption[] = [
    5, 10, 15, 20, 25, 50, 75, 100, 150, 200, 250,
].map((miles) => ({
    value: String(miles),
    label: `${miles} miles`,
}));

/**
 * Human-readable tooltip text explaining why the proximity controls are disabled.
 * Keyed by GelocationStatus.
 */
const GEOLOCATION_DISABLED_REASON: Partial<Record<GeolocationStatus, string>> = {
    idle: 'Click "Use my location" to enable proximity filtering.',
    requesting: 'Waiting for location permission...',
    denied: 'Location access was denied. Please allow location access in your browser settings and try again.',
    unavailable: 'Your location could not be determined. Please check your browser or device settings.',
};

export default function MillFilters({
    headline = 'Mill List',
    textSearch = '',
    states,
    counties = [],
    millTypes,
    woodSpecies,
    onTextSearchChange,
    onStateSelectChange,
    onCountySelectChange,
    onMillTypesSelectChange,
    onWoodSpeciesSelectChange,
    onClearFiltersClick,
    // onExportClick,
    filterResetKey = 0,
    isLoading = false,
    isDownloading = false,
    searchParams = {},
    className = [],
    geolocationStatus = 'idle',
    onRequestLocationClick,
    onRadiusSelectChange,
    isLocationActive = false,
    onCustomLocationSubmit,
    onResetLocationClick,
    isGeocoding = false,
    geocodeError = null,
    locationLabel = null,
    millCount = 0,
    loadError = false,
    onShowResultsClick,
    screenReaderHeading,
    announceStatus = false,
    ...props
}: MillFiltersProps) {

    const countiesDisabled: boolean = (counties && counties.length && counties.length > 0) ? false : true;

    /** Whether the custom-location address form is currently displayed in place of the two location buttons. */
    const [isChoosingLocation, setIsChoosingLocation] = useState(false);
    /** Controlled value of the custom-location address input. */
    const [addressInput, setAddressInput] = useState('');

    const handleCustomLocationFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();
        const trimmed = addressInput.trim();
        if (!trimmed) return;
        onCustomLocationSubmit(trimmed);
    };

    const handleResetClick: MouseEventHandler<HTMLButtonElement> = (event) => {
        setIsChoosingLocation(false);
        setAddressInput('');
        onResetLocationClick(event);
    };

    // const [position, setPosition] = useState<GeolocationPosition | null>(null);

    // geolocation hook
    // const geolocated = useGeolocated({
    //     positionOptions: {
    //         enableHighAccuracy: false,
    //     },
    //     // prevents immediate lookup
    //     // should we even do that?
    //     suppressLocationOnMount: true,
    //     onError: (positionError: GeolocationPositionError | undefined) => {
    //         console.error('Geolocation Error: ', positionError);
    //     },
    //     onSuccess: (position: GeolocationPosition) => {
    //         console.log('Geolocation success!', position);
    //         setPosition(position);
    //         // return position;
    //     }
    // });

    // geolocation properties
    const proximityDisabledReason = GEOLOCATION_DISABLED_REASON[geolocationStatus];

    return (
        <div data-thing="filter-wrap"
            className={cn("flex w-full flex-row items-stretch max-w-screen lg:max-w-full bg-nature lg:bg-transparent lg:max-h-[80vh] lg:overflow-y-auto z-101", className)}
            {...props}>

            <div data-thing="filter-inner-wrap" className="grid gap-1 py-4 lg:py-8 min-w-screen lg:min-w-full px-0 z-101">
                <div data-thing="filter-header" className="flex flex-row lg:flex-row lg:px-8">
                    {screenReaderHeading ? (
                        <>
                            <h2 className="sr-only">{screenReaderHeading}</h2>
                            {/* looks like the heading, but isn't one: see screenReaderHeading */}
                            <p aria-hidden="true" className="text-[31px] lg:text-3xl font-bold text-beluga pb-2 px-6 lg:px-0">{headline}</p>
                        </>
                    ) : (
                        <h2 className="text-[31px] lg:text-3xl font-bold text-beluga pb-2 px-6 lg:px-0">{headline}</h2>
                    )}

                    {/** mill count */}
                    <div className="justify-self-end text-beluga ml-auto  self-center mr-5 lg:mr-0" aria-hidden="true">{millCountText(millCount)}</div>
                    {/* the open mobile Sheet hides the title bar's live region from screen readers, so announce results here too */}
                    {announceStatus && (
                        <span role="status" className="sr-only">{millStatusMessage({ isLoading, loadError, millCount })}</span>
                    )}
                </div>

                {/** 
                 * Text search
                 */}
                <FieldGroup 
                    data-el="FieldGroup"
                    className="gap-5 px-6 pt-2 lg:pt-0 pb-2 lg:px-8 relative before:absolute before:h-px before:w-screen before:bg-beluga before:-top-1 before:left-0 lg:before:hidden after:absolute after:h-px after:w-screen after:bg-beluga after:-bottom-1 after:left-0 lg:after:hidden"
                >
                    {/* text search */}
                    <Field>
                        <FieldLabel className="sr-only" htmlFor="q">Search mill names</FieldLabel>
                        <InputGroup className="rounded-2xl bg-beluga dark:bg-beluga has-[[data-slot=input-group-control]:focus-visible]:ring-coupe">
                            <InputGroupInput 
                                id="q"
                                className="text-velvet dark:text-velvet"
                                placeholder="Search mill names..."
                                value={textSearch || searchParams.q || ''}
                                onChange={onTextSearchChange}
                            />
                            {/* */}
                            <InputGroupAddon align="inline-end">
                                <InputGroupButton                            
                                    aria-label="Search mill names"
                                    title="Search mill names"
                                    size="icon-sm"
                                    className="bg-coupe text-beluga rounded-full"
                                >
                                    <SearchIcon />
                                </InputGroupButton>
                            </InputGroupAddon>
                        </InputGroup>
                    </Field>
                </FieldGroup>

                {/** 
                 * The following fields are considered filters.
                 * Filters can be hidden
                 * They also need to scroll sometimes or somehow be visible on 
                 * allegedly extra large screens.
                 * ScrollArea doesn't work for some reason inside MapControls
                 */}
 
                <FieldGroup
                    id="mill-filters_D"
                    data-el="second FieldGroup"
                    className="gap-5 px-8 lg:bg-lorne w-full pt-8 pb-8 lg:py-0 X-mt-15 lg:mt-0 lg:overflow-y-auto"
                >
                    {/** Proximity filter */}
                    <Field>
                        <FieldLabel className="text-beluga">
                            Filter by Location:
                        </FieldLabel>
                        {/**
                         * The "Use my location" button and the radius dropdown
                         * are grouped together so they read as a single control.
                         */}
                        <div className="flex flex-col gap-2">
                            {/**
                             * Three mutually exclusive states:
                             * 1. A location is already active (either source) — show the
                             *    normalized address (or a generic message for geolocation)
                             *    plus a link to reset it. isLocationActive is the single
                             *    source of truth here regardless of which button produced
                             *    the coordinates — it's independent of geolocationStatus.
                             * 2. The custom-location form is open — show the address input.
                             *    Reachable independently of geolocationStatus, since typing
                             *    an address doesn't touch the browser's location permission.
                             * 3. Neither of the above — show the two location buttons.
                             */}
                            {isLocationActive ? (
                                <div className="flex flex-col gap-1">
                                    <p className="text-beluga text-sm">
                                        {/* says what's being filtered, so it never reads as on when it isn't */}
                                        Showing mills within <strong>{searchParams.radius} miles</strong> of{' '}
                                        {locationLabel ? (
                                            <strong>{locationLabel}</strong>
                                        ) : (
                                            'your current location'
                                        )}
                                    </p>
                                    <Button
                                        type="button"
                                        onClick={handleResetClick}
                                        className="font-bold text-beluga border hover:bg-beluga hover:text-velvet text-sm text-left w-fit"
                                    >
                                        Reset Location
                                    </Button>
                                </div>
                            ) : isChoosingLocation ? (
                                <form className="flex flex-col gap-2" onSubmit={handleCustomLocationFormSubmit}>
                                    <InputGroup className="rounded-2xl bg-beluga dark:bg-beluga has-[[data-slot=input-group-control]:focus-visible]:ring-coupe">
                                        <InputGroupInput
                                            id="customLocation"
                                            className="text-velvet dark:text-velvet"
                                            placeholder="Enter an address, city, or zip..."
                                            value={addressInput}
                                            onChange={(event) => setAddressInput(event.target.value)}
                                            disabled={isGeocoding}
                                            // this field only appears after the user asks to enter a location, so focusing it is expected
                                            // eslint-disable-next-line jsx-a11y/no-autofocus
                                            autoFocus
                                        />
                                        <InputGroupAddon align="inline-end">
                                            <InputGroupButton
                                                type="submit"
                                                aria-label="Look up location"
                                                title="Look up location"
                                                size="icon-sm"
                                                className="bg-coupe text-beluga rounded-full disabled:opacity-100"
                                                disabled={isGeocoding || !addressInput.trim()}
                                            >
                                                {isGeocoding ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    <SearchIcon />
                                                )}
                                            </InputGroupButton>
                                        </InputGroupAddon>
                                    </InputGroup>
                                    {geocodeError && (
                                        <p className="text-sm text-red-400">{geocodeError}</p>
                                    )}
                                    <Button
                                        type="button"
                                        onClick={() => setIsChoosingLocation(false)}
                                        className="text-beluga border font-bold hover:bg-beluga hover:text-velvet text-sm text-left w-fit"
                                    >
                                        Cancel
                                    </Button>
                                </form>
                            ) : (
                                <div className="flex flex-col gap-2 md:flex-row">
                                    <Button
                                        type="button"
                                        onClick={onRequestLocationClick}
                                        disabled={
                                            geolocationStatus === 'requesting' ||
                                            geolocationStatus === 'denied' ||
                                            geolocationStatus === 'unavailable'
                                        }
                                        className="bg-coupe text-beluga border hover:text-beluga w-full justify-center gap-2 font-bold md:max-w-1/2 md:flex-1 relative"
                                    >
                                        {geolocationStatus === 'requesting' ? (
                                            <Loader2 className="h-4 w-4 animate-spin absolute left-3" />
                                        ) : (
                                            <LocateFixed className="h-4 w-4 absolute left-3" />
                                        )}
                                        {geolocationStatus === 'requesting' ? 'Requesting location…' : 'Use my location'}
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => setIsChoosingLocation(true)}
                                        className="bg-beluga text-velvet border hover:bg-velvet hover:text-beluga w-full justify-center gap-2 font-bold md:max-w-1/2 md:flex-1 relative"
                                    >
                                        <MapPinIcon className="h-4 w-4 absolute left-3" />
                                        Choose location
                                    </Button>
                                </div>
                            )}

                            {/* Radius dropdown — disabled until a location (either source) is active */}
                            <div
                                className="flex flex-col gap-2 mt-2"
                                hidden={
                                    !(
                                    isLocationActive ||
                                    geolocationStatus === 'denied' ||
                                    geolocationStatus === 'unavailable'
                                    )
                                }
                            >
                                <FieldLabel htmlFor="radius" className="text-beluga">
                                    Maximum distance:
                                </FieldLabel>
                                <TooltipProvider>
                                    <Tooltip>
                                        {/**
                                         * Wrapping in a <span> ensures the TooltipTrigger has a
                                         * focusable element even when the InputSelect is disabled,
                                         * so keyboard users still see the explanation.
                                         */}
                                        <TooltipTrigger asChild>
                                            <span className="w-full z-100" hidden={
                                                !(isLocationActive ||
                                                geolocationStatus === 'denied' ||
                                                geolocationStatus === 'unavailable')
                                            }
                                        >
                                                <InputSelect
                                                    // remounted when a location is set or reset, to show the default
                                                    // distance (it only picks up a new value while open)
                                                    key={`${filterResetKey}-${isLocationActive}`}
                                                    options={RADIUS_OPTIONS}
                                                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-200"
                                                    onValueChange={onRadiusSelectChange}
                                                    placeholder="Select a distance..."
                                                    // Reset Location turns the filter off; a cleared distance would
                                                    // leave a location that looks set but doesn't filter
                                                    clearable={false}
                                                    disabled={!isLocationActive}
                                                    value={searchParams.radius || ''}
                                                >
                                                    {(provided) => (
                                                        <InputSelectTrigger
                                                            {...provided}
                                                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet h-9 w-full z-100"
                                                            id="radius"
                                                        />
                                                    )}
                                                </InputSelect>
                                            </span>
                                        </TooltipTrigger>
                                        {!isLocationActive && proximityDisabledReason && (
                                            <TooltipContent side="right" className="max-w-56 z-101">
                                                <p>{proximityDisabledReason}</p>
                                            </TooltipContent>
                                        )}
                                    </Tooltip>
                                </TooltipProvider>

                            </div>
                        </div>
                    </Field>

                    {/* state selector */}
                    <Field data-el="Field">
                        <FieldLabel 
                            htmlFor="state"
                            className="text-white"
                            >State:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={states as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-200"
                            onValueChange={onStateSelectChange}
                            placeholder="Select a state..."
                            clearable={true}
                            value={searchParams.state || ''}
                        >
                            {(provided) => (
                                <InputSelectTrigger 
                                    {...provided}
                                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet h-9"
                                    id="state"
                                />
                            )}
                        </InputSelect>
                    </Field>

                    {/* county selector */}
                    <Field>
                        <FieldLabel 
                            htmlFor="county"
                            className="text-white"
                            >County:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={counties as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-200"
                            onValueChange={onCountySelectChange}
                            placeholder="Select a county..."
                            disabled={countiesDisabled}
                            clearable={true}
                            value={searchParams.county || ''}
                        >
                            {(provided) => (
                                <InputSelectTrigger 
                                    {...provided}
                                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet h-9"
                                    id="county"
                                />
                            )}
                        </InputSelect>
                    </Field>

                    {/** Mill Type */}
                    <Field>
                        <FieldLabel 
                            htmlFor="millType"
                            className="text-white"
                            >Mill Type:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={millTypes as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 input-select-popout z-200"
                            onValueChange={onMillTypesSelectChange}
                            placeholder="Select a mill type..."
                            clearable={true}
                            data-thing="mill-type-input-select"
                            value={searchParams.millType || ''}
                        >
                            {(provided) => (
                                <InputSelectTrigger 
                                    {...provided}
                                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet h-9"
                                    id="millType"
                                />
                            )}
                        </InputSelect>
                    </Field>

                    {/** Wood Species */}
                    <Field>
                        <FieldLabel 
                            htmlFor="woodSpecies"
                            className="text-white"
                            >Wood Type:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={woodSpecies as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-200"
                            onValueChange={onWoodSpeciesSelectChange}
                            placeholder="Select a wood type..."
                            clearable={true}
                            value={searchParams.woodSpecies || ''}
                        >
                            {(provided) => (
                                <InputSelectTrigger 
                                    {...provided}
                                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet h-9"
                                    id="woodSpecies"
                                />
                            )}
                        </InputSelect>
                    </Field>

                    <div className="flex flex-row">
                        {/**
                         * Disable clear filters while export is downloading.
                         */}
                        <Button
                            onClick={onClearFiltersClick}
                            className="bg-beluga text-velvet font-bold hover:text-beluga rounded-sm"
                            disabled={isDownloading || isLoading}
                        >Clear Filters</Button>
                        {/**
                         * Closes the mobile filters Sheet. Only shown when the current filters
                         * found mills; disabled while a new search is loading.
                         */}
                        {onShowResultsClick && !loadError && millCount > 0 ? (
                            <Button
                                className="ml-auto bg-coupe text-beluga border font-bold hover:text-beluga rounded-sm"
                                disabled={isLoading}
                                onClick={onShowResultsClick}
                            >Show Results</Button>
                        ) : ''}
                    </div>

                </FieldGroup>
            </div>                            
        </div>

    );
}