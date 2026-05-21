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
import { Spinner } from '@/components/ui/spinner';
import {
    LocateFixed,
    Loader2,
    SearchIcon,
    // SlidersHorizontalIcon,
} from 'lucide-react';
import {
    ChangeEvent,
    MouseEventHandler,
    useState,
} from 'react';
import {
    InputSelect,
    InputSelectTrigger,
} from "@/components/extend/input-select";
import { ClassValue } from "clsx";
// import { useIsMobile } from '@/hooks/use-mobile';
// import { ScrollArea } from "@/components/ui/scroll-area";
import { useGeolocated } from "react-geolocated";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";

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
    onExportClick: MouseEventHandler;
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
    geolocationStatus: GeolocationStatus;
    onRequestLocationClick: MouseEventHandler<HTMLButtonElement>;
    onRadiusSelectChange: (radius: string) => void;

    // add a mill count
    millCount?: number;
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
    onExportClick,
    filterResetKey = 0,
    isLoading = false,
    isDownloading = false,
    searchParams = {},
    className = [],
    geolocationStatus = 'idle',
    onRequestLocationClick,
    onRadiusSelectChange,
    millCount = 0,
    ...props
}: MillFiltersProps) {

    const countiesDisabled: boolean = (counties && counties.length && counties.length > 0) ? false : true;

    const [position, setPosition] = useState<GeolocationPosition | null>(null);

    // geolocation hook
    const geolocated = useGeolocated({
        positionOptions: {
            enableHighAccuracy: false,
        },
        // prevents immediate lookup
        // should we even do that?
        suppressLocationOnMount: true,
        onError: (positionError: GeolocationPositionError | undefined) => {
            console.error('Geolocation Error: ', positionError);
        },
        onSuccess: (position: GeolocationPosition) => {
            console.log('Geolocation success!', position);
            setPosition(position);
            // return position;
        }
    });

    // geolocation properties
    const proximityEnabled = geolocationStatus === 'granted';
    const proximityDisabledReason = GEOLOCATION_DISABLED_REASON[geolocationStatus];

    /** 
     * mobile detection hook 
     */
    // const isMobile = useIsMobile();
    // const [isOpen, setIsOpen] = useState(!isMobile);

    console.log('millFilters.searchParams: ', searchParams);

    return (
        <div data-thing="filter-wrap"
            className={cn("flex w-full flex-row items-stretch max-w-screen lg:max-w-full Xbg-nature lg:transparent lg:max-h-[80vh] lg:overflow-y-auto bg-cyan-500", className)}
            {...props}>

            <div data-thing="filter-inner-wrap" className="grid gap-1 py-4 lg:py-8 Xlg:bg-lorne min-w-screen px-0 z-101 bg-amber-500">
                <div data-thing="filter-header" className="flex flex-row lg:flex-row lg:px-8">
                    {/** This should perhaps be an h1 */}
                    <h2 className="text-[31px] lg:text-3xl font-bold text-beluga pb-2 px-6">{headline}</h2>

                    {/** mill count */}
                    <div className="justify-self-end text-beluga ml-auto  self-center mr-5 bg-green-500">{millCount} mills found</div>                    
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
                        <FieldLabel className="sr-only" htmlFor="q">Text Search</FieldLabel>
                        <InputGroup className="rounded-2xl bg-beluga dark:bg-beluga has-[[data-slot=input-group-control]:focus-visible]:ring-coupe">
                            <InputGroupInput 
                                id="q"
                                className="text-velvet dark:text-velvet"
                                placeholder="Search mills..."
                                value={textSearch || searchParams.q || ''}
                                onChange={onTextSearchChange}
                            />
                            {/* */}
                            <InputGroupAddon align="inline-end">
                                <InputGroupButton                            
                                    aria-label="Search mills"
                                    title="Search mills"
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
                    // hidden={!isOpen}
                    // aria-hidden={!isOpen}
                >
                    {/**
                     * This crap makes filters too tall for the map.
                     */}
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
                            {/* Location request button */}
                            <Button
                                type="button"
                                onClick={onRequestLocationClick}
                                disabled={
                                    geolocationStatus === 'requesting' ||
                                    geolocationStatus === 'granted' ||
                                    geolocationStatus === 'denied' ||
                                    geolocationStatus === 'unavailable'
                                }
                                className="bg-coupe text-beluga hover:text-beluga w-full justify-start gap-2 font-bold"
                                hidden={geolocationStatus === 'granted'}
                            >
                                {geolocationStatus === 'requesting' ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <LocateFixed className="h-4 w-4" />
                                )}
                                {geolocationStatus === 'granted'
                                    ? 'Location granted'
                                    : geolocationStatus === 'requesting'
                                    ? 'Requesting location…'
                                    : 'Use my location'}
                            </Button>

                            {/* Radius dropdown — disabled until location is granted */}
                            <div
                                hidden={
                                    !(
                                    geolocationStatus === 'granted' ||
                                    geolocationStatus === 'denied' ||
                                    geolocationStatus === 'unavailable'
                                    )
                                } 
                            >
                                <TooltipProvider>
                                    <Tooltip>
                                        {/**
                                         * Wrapping in a <span> ensures the TooltipTrigger has a
                                         * focusable element even when the InputSelect is disabled,
                                         * so keyboard users still see the explanation.
                                         */}
                                        <TooltipTrigger asChild>
                                            <span className="w-full z-100" hidden={
                                                !(geolocationStatus === 'granted' ||
                                                geolocationStatus === 'denied' ||
                                                geolocationStatus === 'unavailable')
                                            }
                                        >
                                                <InputSelect
                                                    key={filterResetKey}
                                                    options={RADIUS_OPTIONS}
                                                    className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-100"
                                                    onValueChange={onRadiusSelectChange}
                                                    placeholder="Select a radius..."
                                                    clearable={true}
                                                    disabled={!proximityEnabled}
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
                                        {!proximityEnabled && proximityDisabledReason && (
                                            <TooltipContent side="right" className="max-w-56 z-101">
                                                <p>{proximityDisabledReason}</p>
                                            </TooltipContent>
                                        )}
                                    </Tooltip>
                                </TooltipProvider>

                            </div>
                        </div>
                    </Field>

                    {/** my DIY location stuff using react-geolocated */}
                    <div className="hidden Xflex flex-col text-beluga text-[16px]">
                        <div className="font-bold">Filter by Location:</div>
                        {position && position.coords ? (
                            <div>
                                <strong>Latitude: {position.coords.latitude}</strong>
                                <br />
                                <strong>Longitude: {position.coords.longitude} </strong>
                            </div>
                        ) : <div><strong>Location not determined.</strong></div>
                        }
                        
                        <div className="flex flex-row mt-3">
                            <Button 
                                className="bg-beluga text-velvet rounded-sm font-bold hover:text-beluga"
                                onClick={() => geolocated.getPosition()}
                            >Use Location</Button>
                            {/* <Button className="bg-coupe text-beluga border border-beluga rounded-sm font-bold ml-auto">View Results</Button> */}
                        </div>
                    </div>

                    {/* state selector */}
                    <Field data-el="Field">
                        <FieldLabel 
                            htmlFor="state"
                            className="text-white"
                            >State:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={states as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-100"
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
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-100"
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
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 input-select-popout z-100"
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
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0 z-100"
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
                            disabled={isDownloading}
                        >Clear Filters</Button>
                        {/**
                         * Display Spinner when either export is downloading or mill data is being fetched
                         */}
                        {isLoading || isDownloading ? (
                            <Spinner data-icon="inline-end" className="ml-auto size-8" />
                        ) : ''}
                        {/**
                         * We need to put the export button somewhere, but the spinner somewhat complicates the situation.
                         * Actually, we might want to just submit the form, so to speak.
                         * By which I mean that the filters form already contains all the search parameters needed to query the 
                         * DB and get the same list of mills.
                         */}
                        <Button                            
                            className="ml-auto text-beluga! font-bold rounded-sm"
                            onClick={onExportClick}
                            disabled={isDownloading}
                        >
                            Export
                        </Button>
                    </div>

                </FieldGroup>
                {/* 
                ScrollArea doesn't work here for some reason...
                </ScrollArea> */}
            </div>                            
        </div>

    );
}