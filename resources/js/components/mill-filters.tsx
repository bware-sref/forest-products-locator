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
    SlidersHorizontalIcon,
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

import { useIsMobile } from '@/hooks/use-mobile';

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
    searchParams?: SearchParams;

    onTextSearchChange: (event: ChangeEvent<HTMLInputElement>) => void;
    onStateSelectChange: (stateId: string) => void;
    onCountySelectChange: (countyId: string) => void;
    onMillTypesSelectChange: (millTypeId: string) => void;
    onWoodSpeciesSelectChange: (woodSpeciesId: string) => void;

    // add to that ClearFilters
    onClearFiltersClick: MouseEventHandler;
    /**
     * Incremented by the parent when all filters are cleared. Applied as the
     * `key` prop on each InputSelect so React remounts them, resetting their
     * internal state and restoring the placeholder label.
     */
    filterResetKey?: number;
    isLoading?: boolean;
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
    filterResetKey = 0,
    isLoading = false,
    ...props
}: MillFiltersProps) {

    const countiesDisabled: boolean = (counties && counties.length && counties.length > 0) ? false : true;

    /** 
     * mobile detection hook 
     */
    const isMobile = useIsMobile();
    const [isOpen, setIsOpen] = useState(!isMobile);

    return (
        <div 
            className="flex w-full flex-row items-stretch max-w-screen lg:max-w-90 bg-nature lg:bg-lorne" 
            {...props}>

            <div className="grid gap-1 py-4 lg:py-8 w-full">
                <div className="flex flex-row lg:px-8">
                    {/** This should perhaps be an h1 */}
                    <h2 className="text-[31px] lg:text-3xl font-bold text-beluga pb-2">{headline}</h2>

                    {/** here is where our dropdown trigger goes */}
                    <Button
                        className="bg-coupe border border-beluga text-beluga text-[16px] font-bold justify-self-end ml-auto rounded-sm"
                        onClick={() => setIsOpen(!isOpen)}
                        aria-controls="mill-filters_D"
                    >
                        Filters
                        <SlidersHorizontalIcon
                            data-icon="inline-end"                            
                            className="w-6 h-6 ml-2 size-1"
                        />
                    </Button>
                    
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
                                value={textSearch || ''}
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
                 */}
                <FieldGroup
                    id="mill-filters_D"
                    data-el="second FieldGroup"
                    className="gap-5 px-8 bg-lorne w-full pt-8 pb-8 lg:py-0 -mt-15 lg:mt-0 z-50"
                    hidden={!isOpen}
                    aria-hidden={!isOpen}
                >
                    {/* state selector */}
                    <Field data-el="Field">
                        <FieldLabel 
                            htmlFor="state"
                            className="text-white"
                            >State:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={states as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0"
                            onValueChange={onStateSelectChange}
                            placeholder="Select a state..."
                            clearable={true}                            
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
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0"
                            onValueChange={onCountySelectChange}
                            placeholder="Select a county..."
                            disabled={countiesDisabled}
                            clearable={true}                            
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
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0"
                            onValueChange={onMillTypesSelectChange}
                            placeholder="Select a mill type..."
                            clearable={true}
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
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0"
                            onValueChange={onWoodSpeciesSelectChange}
                            placeholder="Select a wood type..."
                            clearable={true}                            
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
                        <Button
                            onClick={onClearFiltersClick}
                            className="bg-beluga text-velvet font-bold hover:text-beluga"
                        >Clear Filters</Button>
                        {isLoading ? (
                            <Spinner data-icon="inline-end" className="ml-auto size-8" />
                        ) : ''}
                    </div>
                </FieldGroup>
            </div>                            
        </div>

    );
}