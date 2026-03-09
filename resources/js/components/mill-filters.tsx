// import { cn } from "@/lib/utils";
import {
    type County,
    type MillType,
    type State,
    type WoodSpecies,
    // type MillFiltersProps,
} from '@/types';
// import { 
//     ChangeEventHandler,
//     useEffect,
//     useState,    
// } from 'react';
import {
    Field,
    // FieldDescription,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from "@/components/ui/input-group";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    // SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
// import { Button } from '@/components/ui/button';
import {
    SearchIcon
} from 'lucide-react';
import { FilterSelect } from "@/components/filter-select";

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
    states: State[];
    counties?: County[];
    millTypes?: MillType[];
    woodSpecies?: WoodSpecies[];
    /**
     * Probably need props for selected values of each select box.
     */
    // onTextSearchChange: (event: Event) => void;
    onStateSelectChange: (event: Event|string) => void;
    onCountySelectChange: (event: Event|string) => void;
    onMillTypesSelectChange: (event: Event|string) => void;
    onWoodSpeciesSelectChange: (event: Event|string) => void;
}

export default function MillFilters({...props}: MillFiltersProps) {

    const headline = props.headline || 'Mill List';
    const states = props.states;
    const counties = props.counties || [];
    const millTypes = props.millTypes;
    const woodSpecies = props.woodSpecies;
    const countiesDisabled: boolean = (counties && counties.length && counties.length > 0) ? false : true;

    return (
        <div className="flex w-full flex-row items-stretch max-w-83.75">
            <div className="grid gap-1 bg-nature p-8 w-full">
                <h2 className="text-xl font-bold text-beluga pb-2">{headline}</h2>
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
                    <div className="text-beluga"><strong>Filter by Location</strong></div>
                    <div className="text-beluga">We need a reverse geocoding service to determine City, State ZIP from coordinates.</div>
                    {/* state selector */}
                    <Field>
                        <FieldLabel 
                            htmlFor="state"
                            className="text-white"
                            >State:</FieldLabel>
                        <Select 
                            defaultValue=""
                            onValueChange={props.onStateSelectChange}                            
                        >
                            <SelectTrigger id="state"
                                className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet [&_svg:not([class*='text-'])]:text-velvet! [&_svg]:opacity-100 focus-visible:ring-coupe"
                            >
                                <SelectValue 
                                    placeholder="Select a state"
                                    className="bg-beluga! text-velvet"
                                />
                            </SelectTrigger>
                            <SelectContent className="bg-beluga text-velvet">
                                <SelectGroup className="bg-beluga text-velvet">
                                    {states.length > 0 && states.map(state => 
                                        <SelectItem 
                                            key={state.abbreviation}
                                            value={state.abbreviation}
                                        >{state.name}</SelectItem>
                                    )}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </Field>
                    {/* county selector */}
                    <Field>
                        <FieldLabel 
                            htmlFor="county"
                            className="text-white"
                            >County:</FieldLabel>
                        <Select 
                            defaultValue=""
                            onValueChange={props.onCountySelectChange}
                            disabled={countiesDisabled}
                        >
                            <SelectTrigger id="county"
                                className="rounded-none bg-beluga! text-velvet! data-placeholder:text-velvet [&_svg:not([class*='text-'])]:text-velvet! [&_svg]:opacity-100 focus-visible:ring-coupe"
                            >
                                <SelectValue 
                                    placeholder="Select a county"
                                    className="bg-beluga! text-velvet"
                                />
                            </SelectTrigger>
                            <SelectContent className="bg-beluga text-velvet">
                                <SelectGroup className="bg-beluga text-velvet">
                                    {counties && counties.length > 0 && counties.map(county => 
                                        <SelectItem 
                                            key={county.id}
                                            value={county.id.toString()}
                                        >{county.name}</SelectItem>
                                    )}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </Field>
                    {/** Mill Types */}
                    <FilterSelect 
                        id="millTypes"
                        labelText="Mill Type:"
                        placeholder="Select a Mill Type..."
                        options={millTypes}
                        callback={props.onMillTypesSelectChange}
                    />
                    {/** Wood Species */}
                    <FilterSelect 
                        id="woodSpecies"
                        labelText="Wood Type:"
                        placeholder="Select a Wood Type..."
                        options={woodSpecies}
                        callback={props.onWoodSpeciesSelectChange}
                    />
                </FieldGroup>
                <div>
                </div>
            </div>                            
        </div>

    );
}