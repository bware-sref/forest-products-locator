import { cn } from "@/lib/utils";
import {
    type County,
    type MillType,
    type State,
    type WoodSpecies,
    type SelectOption,
    type SearchParams,
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
}

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
    ...props
}: MillFiltersProps) {

    const countiesDisabled: boolean = (counties && counties.length && counties.length > 0) ? false : true;

    /** 
     * mobile detection hook 
     */
    // const isMobile = useIsMobile();
    // const [isOpen, setIsOpen] = useState(!isMobile);

    console.log('millFilters.searchParams: ', searchParams);

    return (
        <div data-thing="filter-wrap"
            className={cn("flex w-full flex-row items-stretch max-w-screen lg:max-w-90 bg-nature lg:bg-lorne ", className)}
            {...props}>

            <div data-thing="filter-inner-wrap" className="grid gap-1 py-4 lg:py-8 w-full">
                <div data-thing="filter-header" className="flex flex-row lg:px-8">
                    {/** This should perhaps be an h1 */}
                    <h2 className="text-[31px] lg:text-3xl font-bold text-beluga pb-2">{headline}</h2>

                    {/** here is where our dropdown trigger goes */}

                    {/* <Button
                        className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end ml-auto rounded-sm"
                        onClick={() => setIsOpen(!isOpen)}
                        aria-controls="mill-filters_D"
                    >
                        Filters
                        <SlidersHorizontalIcon
                            data-icon="inline-end"                            
                            className="w-6 h-6 ml-2 size-1"
                        />
                    </Button> */}
                    
                </div>

                <FieldGroup 
                    data-el="FieldGroup"
                    className="gap-5 pt-2 lg:pt-0 pb-2 lg:px-8 relative before:absolute before:h-px before:w-screen before:bg-beluga before:-top-1 before:-left-4 lg:before:hidden after:absolute after:h-px after:w-screen after:bg-beluga after:-bottom-1 after:-left-4 lg:after:hidden"
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
                {/* <ScrollArea > */}
 
                <FieldGroup
                    id="mill-filters_D"
                    data-el="second FieldGroup"
                    className="gap-5 px-8 lg:bg-lorne w-full pt-8 pb-8 lg:py-0 X-mt-15 lg:mt-0"
                    // hidden={!isOpen}
                    // aria-hidden={!isOpen}
                >
                    {/**
                     * This crap makes filters too tall for the map.
                     */}

                    {/* <div className="hidden flex flex-col text-beluga text-[16px]">
                        <div className="font-bold">Filter by Location:</div>
                        <div><strong>City, ST ZIIIP</strong> within <strong>XXX Miles</strong></div>
                        <div className="flex flex-row mt-3">
                            <Button className="bg-beluga text-velvet rounded-sm font-bold hover:text-beluga">Edit Location</Button>
                            <Button className="bg-coupe text-beluga border border-beluga rounded-sm font-bold ml-auto">View Results</Button>
                        </div>
                    </div>
 */}
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