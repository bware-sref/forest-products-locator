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
} from 'lucide-react';
import { ChangeEvent, MouseEventHandler } from 'react';
import {
    InputSelect,
    InputSelectTrigger,
} from "@/components/extend/input-select";

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

export default function MillFilters({...props}: MillFiltersProps) {

    const headline = props.headline || 'Mill List';
    const states = props.states;
    const counties = props.counties || [];
    const millTypes = props.millTypes;
    const woodSpecies = props.woodSpecies;
    const filterResetKey = props.filterResetKey ?? 0;
    const isLoading = props.isLoading ?? false;
    const countiesDisabled: boolean = (counties && counties.length && counties.length > 0) ? false : true;

    return (
        <div 
            className="flex w-full flex-row items-stretch max-w-89 bg-lorne" 
            {...props}>
            <div className="grid gap-1 Xbg-nature p-4 w-full">
                {/** This should perhaps be an h1 */}
                <h2 className="text-[31px] lg:text-xl font-bold text-beluga pb-2">{headline}</h2>
                {/**
                 * mess
                 */}
                <FieldGroup className="gap-5">
                    {/* text search */}
                    <Field>
                        <FieldLabel className="sr-only" htmlFor="q">Text Search</FieldLabel>
                        <InputGroup className="rounded-2xl bg-beluga dark:bg-beluga has-[[data-slot=input-group-control]:focus-visible]:ring-coupe">
                            <InputGroupInput 
                                id="q"
                                className="text-velvet dark:text-velvet"
                                placeholder="Search mills..."
                                value={props.textSearch || ''}
                                onChange={props.onTextSearchChange}
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
                    {/* state selector */}
                    <Field>
                        <FieldLabel 
                            htmlFor="state"
                            className="text-white"
                            >State:</FieldLabel>
                        <InputSelect
                            key={filterResetKey}
                            options={states as SelectOption[]}
                            className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet p-0"
                            onValueChange={props.onStateSelectChange}
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
                            onValueChange={props.onCountySelectChange}
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
                            onValueChange={props.onMillTypesSelectChange}
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
                            onValueChange={props.onWoodSpeciesSelectChange}
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
                            onClick={props.onClearFiltersClick}
                            className="bg-beluga text-coupe hover:text-beluga"
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